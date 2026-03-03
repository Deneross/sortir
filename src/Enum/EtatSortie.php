<?php
// src/Enum/EtatSortie.php

namespace App\Enum;

enum EtatSortie: int
{
    case EN_CREATION = 1;
    case OUVERTE = 2;
    case CLOTUREE = 3;
    case EN_COURS = 4;
    case TERMINEE = 5;
    case ANNULEE = 6;
    case HISTORISEE = 7;
    case INVALIDE = 8;

    public function label(): string
    {
        return match($this) {
            self::EN_CREATION => 'En création',
            self::OUVERTE => 'Ouverte',
            self::CLOTUREE => 'Clôturée',
            self::EN_COURS => 'En cours',
            self::TERMINEE => 'Terminée',
            self::ANNULEE => 'Annulée',
            self::HISTORISEE => 'Historisée',
            self::INVALIDE => 'Invalide',
        };
    }
}
