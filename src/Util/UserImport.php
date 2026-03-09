<?php

namespace App\Util;

use App\Entity\Campus;
use App\Entity\Participant;
use App\Exception\CampusNotFound;
use App\Repository\CampusRepository;
use App\Repository\ParticipantRepository;
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
        'mot de passe',
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
        private readonly ParticipantRepository       $participantRepo,
        private readonly string                      $fileDirectory = 'app.project_user_update_directory' . '/' . 'app.util.uploader'
    )
    {
    }

    public function readAndGiveDataOfUserImported(FormInterface $form): array
    {
        $users = [];

        $this->injectFileInUploads($form);
        $data = fopen($this->fileDirectory, 'r');

        try {
            $this->validateHeader(fgetcsv($data));
        } catch (RuntimeException $e) {
            throw new RuntimeException(
                'Erreur de construction du fichier d\'import : ' .
                $e->getMessage() .
                ' Import annulé.'
            );
        }

        $ligneConcerned = 1;

        //todo : mapper les donner
        //todo : crer le tableau Resultat à deux tableau User et Erreur

        while (($row = fgetcsv($data)) !== false) {
            //todo : boucler avec les erreurs et les users
        }

        //todo : retourner le tableau Resultat
        return $users;
    }

    public function endImportProcess(): void
    {
        unlink($this->fileDirectory);
    }


    /********* Mes méthode pour la bonne gestion de l'import *********/
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

    private function settingUser(array $data, array &$allPseudosGettingImported, array &$allEmailsGettingImported): Participant
    {
        $newUser = new Participant();

        //Gestion du pseudo
        $pseudoGiven = $data['pseudo'];
        $newUser->setPseudo($this->validationUnicPseudo($pseudoGiven, $allPseudosGettingImported));
        $allPseudosGettingImported[] = $pseudoGiven;

        $this->hasher->hashPassword($newUser, $data['mot de passe']);

        $newUser->setNom(filter_var($data['nom'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $newUser->setPrenom(filter_var($data['prenom'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $newUser->setTelephone(filter_var($data['telephone'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));

        //Gestion du mail/email
        $emailGiven = $data['mail'];
        $newUser->setMail($this->validationUnicEmail($emailGiven, $allEmailsGettingImported));
        $allEmailsGettingImported[] = $pseudoGiven;

        $newUser->setCampus($this->validationCampus($data['campus']));

        switch ($data['role']) {
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

        return $newUser;
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

    private function validationUnicEmail(string $emailGiven, array $emailsGettingImported): string
    {
        $startErrorMessage = 'Erreur d\'unicité sur l\'adresse mail ! ';
        if ($this->participantRepo->findOneBy(['email' => $emailGiven])) {
            throw new RuntimeException(
                $startErrorMessage .
                'Un utilisateur avec la même adresse mail existe déjà.'
            );
        }

        foreach ($emailsGettingImported as $email) {
            if ($email === $emailGiven) {
                throw new RuntimeException(
                    $startErrorMessage .
                    'L\'adresse mail est déjà utilisé sur une autre ligne.'
                );
            }
        }
        return $emailGiven;
    }

    private function validationUnicPseudo(string $pseudoGiven, array $pseudosGettingImported): string
    {
        $startErrorMessage = 'Erreur d\'unicité sur le pseudo ! ';
        if ($this->participantRepo->findOneBy(['pseudo' => $pseudoGiven])) {
            throw new RuntimeException(
                $startErrorMessage .
                'Un utilisateur avec le même pseudo existe déjà.'
            );
        }

        foreach ($pseudosGettingImported as $pseudo) {
            if ($pseudo === $pseudoGiven) {
                throw new RuntimeException(
                    $startErrorMessage .
                    'Le pseudo est déjà utilisé sur une autre ligne.'
                );
            }
        }
        return $pseudoGiven;
    }

    private function validationCampus(string $campusGiven): Campus
    {
        $campus = $this->campusRepo->findOneBy(['name' => $campusGiven]);
        if (!$campus) {
            throw new RuntimeException('Ce Campus n\'existe pas');
        }
        return $campus;
    }
}
