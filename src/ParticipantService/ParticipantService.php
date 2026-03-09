<?php


namespace App\ParticipantService;


use App\Entity\Campus;
use App\Entity\Participant;
use App\Util\ImgManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


final class ParticipantService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ImgManager $service,
        private UserPasswordHasherInterface $toHash,
    ) {
    }

    public function updateParticipant(FormInterface $form,Participant $participant,?Campus $campus = null): Participant
    {
        if ($campus !== null) {
            $participant->setCampus($campus);
        }

        // Mise à jour mot de passe si rempli
        $pwdChanged = $form->get('newPassword')->getData();
        if (!empty($pwdChanged)) {
            $participant->setPassword($this->toHash->hashPassword($participant, $pwdChanged));
        }

        // Gestion image
        $participant = $this->service->updateImg($participant, $form);

        $this->em->flush();

        return $participant;
    }
}
