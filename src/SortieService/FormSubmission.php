<?php

namespace App\SortieService;

use App\Entity\Campus;
use App\Entity\Sortie;
use App\Enum\EtatSortie;
use App\Exception\EtatError;
use App\Exception\ParticipantNotFound;
use App\Exception\SortieIllegalUpdate;
use App\Exception\SortieNotFound;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use App\Util\FromUserToParticipant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;

class FormSubmission
{
    public function __construct(
        private readonly EtatManager            $etatService,
        private readonly EtatRepository         $etatRepo,
        private readonly FromUserToParticipant  $participantService,
        private readonly LieuManager            $lieuService,
        private readonly SortieRepository       $sortieRepo,
        private readonly EntityManagerInterface $em,
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

    public function createSortie(
        Campus        $campus,
        FormInterface $form,
        Sortie        $sortie
    ): void
    {
        try {
            //Gestion du lieu affilié à la sortie
            $lieu = $this->lieuService->createLieuFromSortie($form);
            $this->em->persist($lieu);

            $sortie->addLieux($lieu);
            $sortie->setCampus($campus);
            $this->etatService->setSortieEtatFromForm($sortie, $form);

            $this->em->persist($sortie);

            $this->em->flush();

        } catch (EtatError $e) {
            throw new EtatError(
                $e->getMessage(),
            );
        }
    }

    public function getRightSortie(int $id): Sortie
    {
        $sortie = $this->sortieRepo->findWithJointure($id);
        if (null === $sortie) {
            throw new SortieNotFound();
        }
        return $sortie;
    }

    public function ExceptionIfCannotUpdateSortie(Sortie $sortie): void
    {
        if ($sortie->getOrganisateur() !== $this->participantService->getParticipant()) {
            throw new SortieIllegalUpdate(
                'Seul l\'organisateur peut modifier cette sortie'
            );
        }
        if ($sortie->getEtat() !== $this->etatRepo->find(1)) {
            throw new SortieIllegalUpdate(
                'La sortie est déjà publiée, vous ne pouvez plus la mettre à jour.'
            );
        }
    }

    public function removeSortie(Sortie $sortie): void
    {
        foreach ($sortie->getLieux() as $lieu) {
            $sortie->removeLieux($lieu);
            $this->em->remove($lieu);
        }
        $this->em->remove($sortie);
        $this->em->flush();
    }

    public function updateSortie(Campus $campus, Sortie $sortie, FormInterface $form): void
    {
        $sortie->setCampus($campus);

        foreach ($sortie->getLieux() as $lieu) {
            $this->lieuService->ctrlAndReplaceLieuData($lieu, $form);
            $this->em->persist($lieu);
        }

        $this->etatService->setSortieEtatFromForm($sortie, $form);
        $this->em->persist($sortie);

        $this->em->flush();
    }
}
