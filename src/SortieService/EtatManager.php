<?php

namespace App\SortieService;

use App\Entity\Sortie;
use App\Enum\EtatSortie;
use App\Exception\EtatError;
use App\Repository\EtatRepository;
use Symfony\Component\Form\FormInterface;

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

        if ($sortie->isArchived()) {
            return EtatSortie::HISTORISEE;
        }

        if ($sortie->isCancel()) {
            return EtatSortie::ANNULEE;
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

        if ($sortie->isPublished()) {
            return EtatSortie::OUVERTE;
        }

        return EtatSortie::EN_CREATION;
    }

    public function settingEtat(Sortie $sortie): void {
        $idEtat = $this->getEtat($sortie)->value;
        if(!$idEtat) {
            throw new EtatError('Erreur dans l\'attribution de l\'état de la sortie');
        }

        $etat = $this->etatRepository->find($idEtat);
        if(!$etat){
            throw new EtatError('L\'état transmis est introuvable');
        }

        $sortie->setEtat($etat);
    }

    public function setSortieEtatFromForm(Sortie $sortie, FormInterface $form): void{
        if ($form->get('publier')->isClicked()) {
            $sortie->setPublished(true);
        }
        try {
            $this->settingEtat($sortie);
        } catch (EtatError $e) {
            throw new EtatError($e->getMessage());
        }
    }

    public function etatColorDisplay(Sortie $sortie): string{
        switch ($sortie->getEtat()->getId()) {
            case 1 :
                return 'text-bg-info text-white';
            case 2 :
                return 'text-bg-primary';
            case 3 :
                return 'text-bg-secondary';
            case 4 :
                return 'text-bg-warning';
            case 5 :
                return 'text-bg-success';
            case 6 :
                return 'text-bg-danger';
            case 7 :
                return 'text-bg-dark';
            default :
                return 'text-bg-light';
        }
    }
}
