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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;

class FormSubmission
{
    public function __construct(
        private readonly EtatManager            $etatService,
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
            $this->etatService->setSortieEtat($sortie, $form);

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

    public function removeSortie(Sortie $sortie): void
    {
        //todo : gestion de la suppresion
    }

    public function updateSortie(Campus $campus, Sortie $sortie, FormInterface $form): void
    {
        $sortie->setCampus($campus);

        foreach ($sortie->getLieux() as $lieu) {
            $this->lieuService->ctrlAndReplaceLieuData($lieu, $form);
            $this->em->persist($lieu);
        }

        $this->etatService->setSortieEtat($sortie, $form);
        $this->em->persist($sortie);

        $this->em->flush();
    }
}
