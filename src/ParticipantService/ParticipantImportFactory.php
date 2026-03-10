<?php

namespace App\ParticipantService;

use App\Entity\Campus;
use App\Entity\Participant;
use App\Repository\CampusRepository;
use App\Repository\ParticipantRepository;
use \RuntimeException;

class ParticipantImportFactory
{
    private array $emailsInBdd;
    private array $pseudoInBdd;

    public function __construct(
        private readonly CampusRepository      $campusRepo,
        private readonly ParticipantRepository $participantRepo,
    )
    {
        $this->emailsInBdd = $this->participantRepo->findEmailsInBdd();
        $this->pseudoInBdd = $this->participantRepo->findPseudosInBdd();
    }

    public function settingUser(array $data, array &$allPseudosGettingImported, array &$allEmailsGettingImported): Participant
    {
        $newUser = new Participant();

        //Gestion du pseudo
        $pseudoGiven = trim($data['pseudo']);
        $newUser->setPseudo($this->validationUnicPseudo($pseudoGiven, $allPseudosGettingImported));
        $allPseudosGettingImported[] = $pseudoGiven;

        //Gestion du mot de passe
        $newUser->setPassword(password_hash($data['mot de passe'], PASSWORD_DEFAULT));

        //Tous les champs strings classiques
        $newUser->setNom(trim($data['nom']));
        $newUser->setPrenom(trim($data['prenom']));
        $newUser->setTelephone(trim($data['telephone']));

        //Gestion du mail/email
        $emailGiven = trim($data['mail']);
        $newUser->setMail($this->validationUnicEmail($emailGiven, $allEmailsGettingImported));
        $allEmailsGettingImported[] = $emailGiven;

        //Le campus
        $newUser->setCampus($this->validationCampus(trim($data['campus'])));

        $role = $data['role'] ? trim(mb_strtolower($data['role'])) : null;
        switch ($role) {
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
    private function validationUnicEmail(string $emailGiven, array $emailsGettingImported): string
    {
        $startErrorMessage = 'Erreur d\'unicité sur l\'adresse mail ! ';

        foreach ($this->emailsInBdd as $email) {
            if ($emailGiven === $email) {
                throw new RuntimeException(
                    $startErrorMessage .
                    'Un utilisateur avec la même adresse mail existe déjà.'
                );
            }
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

        foreach ($this->pseudoInBdd as $pseudo) {
            if ($pseudoGiven === $pseudo) {
                throw new RuntimeException(
                    $startErrorMessage .
                    'Un utilisateur avec le même pseudo existe déjà.'
                );
            }
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
        if(!$campus) {
            throw new RuntimeException('Ce Campus n\'existe pas');
        }
        return $campus;
    }
}
