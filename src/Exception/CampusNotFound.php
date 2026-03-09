<?php

namespace App\Exception;

use Exception;

class CampusNotFound extends Exception
{
    public function __construct(
        string $message = 'Le campus semble introuvable.',
        int $code = 418,
    ){
        parent::__construct($message, $code);
    }

}
