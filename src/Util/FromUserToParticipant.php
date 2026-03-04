<?php

namespace App\Util;

use App\Entity\Participant;
use App\Exception\ParticipantNotFound;
use Symfony\Bundle\SecurityBundle\Security;

class FromUserToParticipant
{
    public function __construct(
        private readonly Security $security
    )
    {
    }

    public function getParticipant(): Participant
    {
        $user = $this->security->getUser();

        if (!$user instanceof Participant) {
            throw new ParticipantNotFound(
                'Aucun participant connecté.'
            );
        }

        return $user;
    }

}
