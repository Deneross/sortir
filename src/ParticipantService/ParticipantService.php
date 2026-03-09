<?php


namespace App\ParticipantService;


use App\Entity\Participant;
use App\Util\ImgManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


final class ParticipantService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    public function updateParticipant(ImgManager $service,UserPasswordHasherInterface $toHash,Participant $participant, FormInterface $form): Participant
    {
        $campus = $participant->getCampus();

        // Réinjection du campus si champ disabled
        $participant->setCampus($campus);

        // Mise à jour mot de passe si rempli
        $pwdChanged = $form->get('newPassword')->getData();
        if (!empty($pwdChanged)) {
            $participant->setPassword($toHash->hashPassword($participant, $pwdChanged));
        }

        // Gestion image
        $participant = $service->updateImg($participant, $form);

        $this->em->flush();

        return $participant;
    }
}
