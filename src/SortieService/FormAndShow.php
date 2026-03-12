<?php

namespace App\SortieService;

use App\Entity\Campus;
use App\Entity\Sortie;
use App\Exception\EtatError;
use App\Exception\ParticipantNotFound;
use App\Exception\SortieIllegalDisplay;
use App\Exception\SortieIllegalUpdate;
use App\Exception\SortieNotFound;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use App\Service\FromUserToParticipant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class FormAndShow
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
        Sortie        $sortie,
        Request       $request,
    ): void
    {
        try {
            //Nouvelle gestion du lieu en JSON traité dans son service
            $this->lieuService->createLieuFromJSON($request, $sortie, $form, $this->em);

            /*//Gestion du lieu affilié à la sortie
            $lieu = $this->lieuService->createLieuFromSortie($form);
            $this->em->persist($lieu);
            $sortie->addLieux($lieu);*/

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

    public function publishSortie(Sortie $sortie): void
    {
        $sortie->setPublished(true);
        $this->etatService->settingEtat($sortie);
        $this->em->persist($sortie);
        $this->em->flush();
    }

    public function registerSortie(Sortie $sortie): void
    {
        $sortie->addInscrit($this->participantService->getParticipant());
        $this->em->persist($sortie);
        $this->em->flush();
    }

    public function unRegisterSortie(Sortie $sortie): void
    {
        $sortie->removeInscrit($this->participantService->getParticipant());
        $this->em->persist($sortie);
        $this->em->flush();
    }

    public function cancelSortie(Sortie $sortie, string $motif): void
    {
        $sortie->setCancel(true);

        $sortie->setMotif($motif);

        $this->etatService->settingEtat($sortie);
        $this->em->persist($sortie);
        $this->em->flush();
    }

    public function exceptionIfCannotRead(Sortie $sortie): void
    {
        if ($sortie->getEtat() === $this->etatRepo->find(7)) {
            throw new SortieIllegalDisplay(
                'Le sortie est archivée, il n\'est plus possible de la consulter'
            );
        }
    }
}
