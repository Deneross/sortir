<?php

namespace App\Util;

use App\ParticipantService\ParticipantImportFactory;
use \RuntimeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserImport
{
    private const array ALL_COLUMNS = [
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

                $ligneConcerned = 1;

                $resultat = [
                    'users' => [],
                    'errors' => []
                ];

                $emailImport = [];
                $pseudoImport = [];

                fgetcsv($data);

                while (($row = fgetcsv($data, separator: ";", escape: "")) !== false) {
                    $ligneConcerned++;

                    try {
                        //mapper les donner colonne, ligne + prendre en compte le role qui peut être null
                        $row = array_pad($row, count(self::ALL_COLUMNS), null);
                        $mappedData = array_combine(self::ALL_COLUMNS, $row);
                        try {
                            $user = $this->service->settingUser($mappedData, $pseudoImport, $emailImport);
                            $errors = $this->validator->validate($user);

                            if (count($errors) > 0) {
                                foreach ($errors as $error) {
                                    //Erreur issue des Asserts de Participant
                                    $resultat['errors'][] = 'Certaines saisies ne sont pas bonnes pour l\'import à la ligne ' . $ligneConcerned . ' : ' . $error->getMessage();
                                }
                                continue;
                            }
                            $resultat['users'][] = [
                                'numLigne' => $ligneConcerned,
                                'userData' => $user
                            ];
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
                $this->container->get('app.name_user_update_file');
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
                $this->container->get('app.name_user_update_file')
            );
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
            throw new RuntimeException('Erreur à l\'enregistrement du fichier : ' . $e->getMessage());
        }
    }

    private function endImportProcess(): void
    {
        unlink($this->fileDirectory());
    }
}
