<?php

namespace App\Util\AdminPage;

use App\Entity\Administrable;
use App\Entity\Ville;
use App\Repository\LieuRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\HttpFoundation\Request;

class Factory
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LieuRepository $lieuRepo,
    )
    {
    }

    public function sendToBDDAndUpdateSessionList(Administrable $entity, array $lstSession, Request $request, Filters $filterService): void
    {
        $this->em->persist($entity);
        $this->em->flush();

        $lstSession[$entity->getId()] = $entity;

        $request->getSession()->set($filterService->getNomListSession(), $lstSession);
    }

    public function foundEntity(string $inputIdKey, ServiceEntityRepository $repo, Request $request): Administrable
    {
        $id = $request->request->get($inputIdKey);
        $entity = $repo->find($id);

        if (!$entity) {
            throw new EntityNotFoundException('Erreur à l\'identificaiton.');
        }

        return $entity;
    }

    public function deletingVille(Ville $ville, array $lstSession, Request $request, Filters $filterService): void
    {
        $id=$ville->getId();
        if ($this->lieuRepo->canVilleBeDeleted($id)) {
            $this->em->remove($ville);
            $this->em->flush();

            unset($lstSession[$id]);
            $request->getSession()->set($filterService->getNomListSession(), $lstSession);

        } else {
            throw new \Exception('La ou les villes concernées sont utilisés pour les sorties.', 403);
        }
    }

}
