<?php

namespace App\Exception;

use Exception;

class EtatError extends Exception
{
    public function __construct(
        string $message = 'Une erreure s\'est produite au chargement de l\'état.',
        int $code = 418,
    ){
        parent::__construct($message, $code);
    }

}
