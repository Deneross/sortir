<?php

namespace App\SortieService;

use App\Entity\Sortie;
use App\Enum\EtatSortie;

class Etat
{
    public function getEtat(Sortie $sortie): EtatSortie
    {
        $now = new \DateTimeImmutable();

        if ($sortie->isCancel()) {
            return EtatSortie::ANNULEE;
        }

        if ($sortie->isArchived()) {
            return EtatSortie::HISTORISEE;
        }

        if (!$sortie->isPublished()) {
            return EtatSortie::EN_CREATION;
        }

        $fin = $sortie->getDateHeureDebut()->add(new \DateInterval("PT{$sortie->getDuree()}M"));

        if ($now > $fin) {
            return EtatSortie::TERMINEE;
        }

        if ($now >= $sortie->getDateHeureDebut()) {
            return EtatSortie::EN_COURS;
        }


        $complet = $sortie->getInscrits()->count() >= $sortie->getNbInscriptionMax();
        $dateLimiteDepassee = $now > $sortie->getDateLimiteInscription();

        if ($complet || $dateLimiteDepassee) {
            return EtatSortie::CLOTUREE;
        }

        return EtatSortie::OUVERTE;
    }
}
