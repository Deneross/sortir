<?php

namespace App\Exception;

use Exception;

class ParticipantNotFound extends Exception
{
    public function __construct(
        string $message = 'Votre profil semble introuvable.',
        int $code = 418,
    ){
        parent::__construct($message, $code);
    }

}
