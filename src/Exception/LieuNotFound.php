<?php

namespace App\Exception;

use Exception;

class LieuNotFound extends Exception
{
    public function __construct(
        string $message = 'Le lieu de la sortie semble ne pas existe pas..',
        int $code = 404,
    ){
        parent::__construct($message, $code);
    }

}
