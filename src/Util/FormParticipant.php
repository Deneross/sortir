<?php

namespace App\Util;

use App\Entity\Participant;
use App\Exception\ParticipantNotFound;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\SecurityBundle\Security;

class FormParticipant
{
    public function __construct(
        private readonly Security $security)
    {
    }

    public function getParticipant():Participant {
        $user = $this->security->getUser();

        if (!$user instanceof Participant) {
            throw new ParticipantNotFound(
                'Aucun participant connecté.'
            );
        }

        return $user;
    }

    public function getProfilPicture(Participant $p):?string {
        //todo: envoyer le bon src de la photo du participant
        return $p->getNomFichierPhoto()?:'/images/default.png';
    }

}
