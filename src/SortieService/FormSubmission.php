<?php

namespace App\SortieService;

use App\Entity\Campus;
use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Enum\EtatSortie;
use App\Exception\EtatError;
use App\Exception\ParticipantNotFound;
use App\Exception\SortieNotFound;
use App\Repository\EtatRepository;
use App\Repository\LieuRepository;
use App\Repository\SortieRepository;
use App\Util\FromUserToParticipant;
use Symfony\Component\Form\FormInterface;

class FormSubmission
{
    public function __construct(
        private readonly EtatManager           $etatService,
        private readonly FromUserToParticipant $participantService,
        private readonly LieuRepository        $lieuRepo,
        private readonly SortieRepository      $sortieRepo,
    )
    {
    }

    public function initialSortie(): Sortie
    {
        try {
            $sortie = new Sortie();
            $participant = $this->participantService->getParticipant();
            $sortie->setOrganisateur($participant);
            $sortie->setCampus($participant->getCampus());
            return $sortie;
        } catch (ParticipantNotFound $e) {
            throw new ParticipantNotFound(
                $e->getMessage(),
            );
        }
    }

    public function setSortieSpecificationsFromCreate(
        Lieu          $lieu,
        Campus        $campus,
        FormInterface $form,
        Sortie        $sortie
    ): void
    {
        try {
            $sortie->addLieux($lieu);
            $sortie->setCampus($campus);
            $this->setSortieEtatFromCreate($sortie, $form);
        } catch (EtatError $e) {
            throw new EtatError(
                $e->getMessage(),
            );
        }
    }

    public function getRightSortie(int $id) : Sortie {
        $sortie = $this->sortieRepo->findWithJointure($id);
        if (null === $sortie) {
            throw new SortieNotFound();
        }
        return $sortie;
    }

    private function setSortieEtatFromCreate(Sortie $sortie, FormInterface $form): void
    {
        try {
            if ($form->get('publier')->isClicked()) {
                $sortie->setEtat($this->etatService->getRightEtat(EtatSortie::OUVERTE));
                $sortie->setPublished(true);
            } else {
                $sortie->setEtat($this->etatService->getRightEtat(EtatSortie::EN_CREATION));
            }
        } catch (EtatError $e) {
            throw new EtatError(
                $e->getMessage(),
            );
        }
    }
}
