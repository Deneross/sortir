<?php

namespace App\Access;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class CheckActifParticipant
{
    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $em,
    )
    {
    }

    public function isParticipantUnactif(): bool{
        $participantCo = $this->security->getUser();
        return $this->controllerActif($participantCo);
    }

    public function isGivenParticipantInactif(Participant $participant): bool{
        return $this->controllerActif($participant);
    }

    private function controllerActif(Participant $participantCo) : bool {
        //Valider qu'aucun inactif essai de se connecter
        if(!$participantCo->isActif()){
            //Si en plus il n'avait pas le bon rôle, on le lui change
            if($participantCo->getRoles() != ['ROLE_USER']){
                $participantCo->setRoles(['ROLE_USER']);
                $this->em->persist($participantCo);
                $this->em->flush();
            }
            //On sort direct en passant au controler que l'utilisateur est inactif
            return true;

            /*
            Si le premier if échoue, (ndlr: il est actif)
            mais que le statut n'est pas le bon,
            on le repasse au bon statut
            */
        }elseif($participantCo->getRoles() === ['ROLE_USER'] ){
            $participantCo->setRoles(['ROLE_PARTICIPANT']);
            $this->em->persist($participantCo);
            $this->em->flush();
        }
        return false;
    }
}
