<?php
namespace App\Exception;

class SortieAlreadyClosed extends \Exception
{
    public function __construct()
    {
        parent::__construct("La sortie est déjà clôturée. Vous ne pouvez plus l'annuler.");
    }
}
