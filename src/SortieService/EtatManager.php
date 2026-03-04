<?php

namespace App\SortieService;

use App\Entity\Sortie;
use App\Enum\EtatSortie;
use App\Exception\EtatError;
use App\Repository\EtatRepository;

class EtatManager
{
    public function __construct(
        private readonly EtatRepository $etatRepository,
    )
    {
    }

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

    public function getRightEtat(EtatSortie $etat): \App\Entity\Etat {
        $etat = $this->etatRepository->find($etat->value);

        if(!$etat){
            throw new EtatError('L\'état transmis est introuvable');
        }

        return $etat;
    }
}
