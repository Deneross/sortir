<?php

namespace App\Exception;

use Exception;

class SortieNotFound extends Exception
{
    public function __construct(
        string $message = 'Cette sortie n\'existe pas..',
        int $code = 404,
    ){
        parent::__construct($message, $code);
    }

}
