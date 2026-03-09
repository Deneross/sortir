<?php

namespace App\Util;

use App\Entity\Participant;
use App\Exception\CampusNotFound;
use App\Repository\CampusRepository;
use http\Exception\RuntimeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validation;

class UserImport
{
    private const ALL_COLUMNS = [
        'pseudo',
        'password',
        'nom',
        'prenom',
        'telephone',
        'mail',
        'campus',
        'role'
    ];

    public function __construct(
        private readonly ContainerBagInterface       $container,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly Validation                  $validate,
        private readonly CampusRepository            $campusRepo,
        private readonly string                      $fileDirectory = 'app.project_user_update_directory' . '/' . 'app.util.uploader'
    )
    {
    }

    public function readAndGiveDataOfUserImported(FormInterface $form): array
    {
        $users = [];

        $this->injectFileInUploads($form);
        $data = fopen($this->fileDirectory, 'r');

        //Pour ignorer la première ligne = entête
        fgetcsv($data);

        $ligneConcerned = 1;

        while (($row = fgetcsv($data)) !== false) {
            $newUser = new Participant();
            $newUser->setPseudo($row[0]);
            $this->hasher->hashPassword($newUser, $row[1]);
            $newUser->setNom($row[2]);
            $newUser->setPrenom($row[3]);
            $newUser->setTelephone($row[4]);
            $newUser->setMail($row[5]);

            $campusGiven = $this->campusRepo->findOneBy(['name' => $row[6]]);
            if ($campusGiven) {
                $newUser->setCampus($campusGiven);
            } else {
                throw new CampusNotFound('Le campus renseigné dans l\'import est introuvable');
            }

            switch ($row[7]) {
                case 'inactif':
                    $newUser->setActif(false);
                    $newUser->setRoles(['ROLE_USER']);
                    break;
                case 'administrateur':
                    $newUser->setRoles(['ROLE_ADMIN']);
                    break;
                default:
                    $newUser->setRoles(['ROLE_PARTICIPANT']);
            }

            $users[] = $newUser;
        }

        return $users;
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

    public function endImportProcess(): void
    {
        unlink($this->fileDirectory);
    }


    /********* Mes règles de validation d'import *********/
    private function validateHeader(array $headerGiven): void
    {
        if ($headerGiven !== self::ALL_COLUMNS) {
            throw new RuntimeException(
                'Vos entêtes d\'import semblent errronés.
                Modifier le fichier en utilisant les colonnes suivantes : ' .
                implode(', ', self::ALL_COLUMNS)
            );
        }
    }

    private function validationUnmissingColumns(mixed $row, int $nbLigneConcerned): void
    {
        if (count($row) !== (count(self::ALL_COLUMNS) - 1)) {
            throw new RuntimeException(
                'Il vous manque des informations à la ligne ' . $nbLigneConcerned .
                ' Toutes les colonnes à l\'exception de la dernière doivent être renseignée.'
            );
        }
    }
}
