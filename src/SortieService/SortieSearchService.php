<?php

namespace App\SortieService;

use App\Entity\Participant;
use App\Repository\SortieRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

final class SortieSearchService
{
    public function __construct(private SortieRepository $sortieRepository) {}

    /**
     * Retourne toutes les sorties (non paginé)
     */
    public function search(array $filters, ?Participant $user): array
    {
        $qb = $this->sortieRepository->qbForList();
        $this->applyFilters($qb, $filters, $user);

        return $qb->orderBy('s.dateHeureDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne un paginator Doctrine (paginé)
     */
    public function searchPaginated(array $filters, ?Participant $user, int $page = 1, int $limit = 10): Paginator
    {
        $page = max(1, $page);
        $limit = max(1, $limit);

        $qb = $this->sortieRepository->qbForList();
        $this->applyFilters($qb, $filters, $user);

        $qb->orderBy('s.dateHeureDebut', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return new Paginator($qb);
    }

    /**
     * Applique tous les filtres à un QueryBuilder
     */
    private function applyFilters(QueryBuilder $qb, array $filters, ?Participant $user): void
    {
        // Campus id
        if (!empty($filters['campus'])) {
            $qb->andWhere('c.id = :campus')
                ->setParameter('campus', (int) $filters['campus']);
        }

        // Recherche texte sur nom
        if (!empty($filters['search'])) {
            $search = mb_strtolower(trim((string) $filters['search']));
            if ($search !== '') {
                $qb->andWhere('LOWER(s.nom) LIKE :search')
                    ->setParameter('search', '%' . $search . '%');
            }
        }

        // Dates (YYYY-MM-DD)
        if (!empty($filters['dateMin'])) {
            $qb->andWhere('s.dateHeureDebut >= :dateMin')
                ->setParameter('dateMin', new \DateTimeImmutable($filters['dateMin'] . ' 00:00:00'));
        }

        if (!empty($filters['dateMax'])) {
            $qb->andWhere('s.dateHeureDebut <= :dateMax')
                ->setParameter('dateMax', new \DateTimeImmutable($filters['dateMax'] . ' 23:59:59'));
        }

        // Terminées (selon libellé)
        if (!empty($filters['terminees'])) {
            $qb->andWhere('LOWER(e.libelle) = :etatTerminee')
                ->setParameter('etatTerminee', 'terminée');
        }

        // Filtres liés à l’utilisateur
        if ($user) {
            $needMe = false;

            if (!empty($filters['orga'])) {
                $qb->andWhere('s.organisateur = :me');
                $needMe = true;
            }

            if (!empty($filters['inscrit'])) {
                $qb->andWhere(':me MEMBER OF s.inscrits');
                $needMe = true;
            }

            if (!empty($filters['nonInscrit'])) {
                $qb->andWhere(':me NOT MEMBER OF s.inscrits');
                $needMe = true;
            }

            if ($needMe) {
                $qb->setParameter('me', $user);
            }
        } else {
            // Si pas connecté et qu’on demande orga/inscrit/nonInscrit => 0 résultat
            if (!empty($filters['orga']) || !empty($filters['inscrit']) || !empty($filters['nonInscrit'])) {
                $qb->andWhere('1 = 0');
            }
        }
    }
}
