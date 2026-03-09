<?php

namespace App\Util;

use App\ParticipantService\ParticipantImportFactory;
use \RuntimeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserImport
{
    private const ALL_COLUMNS = [
        'pseudo',
        'mot de passe',
        'nom',
        'prenom',
        'telephone',
        'mail',
        'campus',
        'role'
    ];

    public function __construct(
        private readonly ContainerBagInterface    $container,
        private readonly ParticipantImportFactory $service,
        private readonly ValidatorInterface       $validator,
    )
    {
    }

    public function readAndGiveDataOfUserImported(FormInterface $form): array
    {
        $this->injectFileInUploads($form);
        try {
            $data = fopen($this->fileDirectory(), 'r');
            try {
                $this->validateHeader(fgetcsv($data));

                $ligneConcerned = 1;

                $resultat = [
                    'users' => [],
                    'errors' => []
                ];

                $emailImport = [];
                $pseudoImport = [];

                while (($row = fgetcsv($data)) !== false) {
                    $ligneConcerned++;
                    try {
                        $this->validationUnmissingColumns($row, $ligneConcerned);

                        //mapper les donner colonne, ligne + prendre en compte le role qui peut être null
                        $row = array_pad($row, count(self::ALL_COLUMNS), null);
                        $data = array_combine(self::ALL_COLUMNS, $row);
                        try {
                            $user = $this->service->settingUser($data, $pseudoImport, $emailImport);
                            $errors = $this->validator->validate($user);

                            if (count($errors) > 0) {
                                foreach ($errors as $error) {
                                    //Erreur issue des Asserts de Participant
                                    $resultat['errors'][] = 'Certaines saisies ne sont pas bonnes pour l\'import à la ligne ' . $ligneConcerned . ' : ' . $error->getMessage();
                                }
                                continue;
                            }
                            $resultat['users'][] = $user;
                        } catch (\Exception $e) {
                            //Erreur issue du service RegleImport
                            $resultat['errors'][] = 'Erreur sur la ligne ' . $ligneConcerned . ' : ' . $e->getMessage();
                        }
                    } catch (\Exception $e) {
                        //Erreur du nb colonnes remplis
                        $resultat['errors'][] = 'Attention ligne ' . $ligneConcerned . ' : ' . $e->getMessage();
                    }
                }

                //retourner le tableau Resultat pour le controler
                return $resultat;

            } catch (RuntimeException $e) {
                throw new RuntimeException(
                    'Erreur de construction du fichier d\'import : ' .
                    $e->getMessage() .
                    ' Import annulé.'
                );
            } finally {
                fclose($data);
                $this->endImportProcess();
            }
        } catch (\Exception $e) {
            throw new \Exception(
                'Erreur interne d\'import : ' .
                $e->getMessage() .
                ' Import annulé.'
            );
        }
    }

    /********* Mes méthode pour la bonne gestion de l'import *********/
    private function fileDirectory(): string
    {
        try {
            return $this->container->get('app.project_user_update_directory') .
                '/' .
                $this->container->get('app.util.uploader');
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
            throw new \Exception($e->getMessage());
        }
    }

    private function injectFileInUploads(FormInterface $form): void
    {
        $file = $form->get('file')->getData();
        try {
            $file->move(
                $this->container->get('app.project_user_update_directory'),
                $this->container->get('app.util.uploader')
            );
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
            throw new RuntimeException('Erreur à l\'enregistrement du fichier', $e->getMessage());
        }
    }

    private function endImportProcess(): void
    {
        unlink($this->fileDirectory());
    }

    /********* Mes règles de validation d'import *********/
    private function validateHeader(array $headerGiven): void
    {
        $data = array_map('trim', $headerGiven);
        if ($data !== self::ALL_COLUMNS) {
            throw new RuntimeException(
                'Vos entêtes d\'import semblent errronés.
                Modifier le fichier en utilisant les colonnes suivantes : ' .
                implode(', ', self::ALL_COLUMNS)
            );
        }
    }

    private function validationUnmissingColumns(mixed $row, int $nbLigneConcerned): void
    {
        if (count($row) < count(self::ALL_COLUMNS) - 1) {
            throw new RuntimeException(
                'Il vous manque des informations à la ligne ' . $nbLigneConcerned .
                ' Toutes les colonnes à l\'exception de la dernière doivent être renseignée.'
            );
        }
    }
}
