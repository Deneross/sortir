<?php

namespace App\Repository;

use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use PhpParser\Node\Expr\Array_;

/**
 * @extends ServiceEntityRepository<Sortie>
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    public function findLastEvents(int $page = 1, int $limit = 10): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder
            ->addOrderBy('c.dateHeureDebut', 'DESC')
            ->addOrderBy('c.dateLimiteInscription', 'DESC')
            ->orderBy('c.nom', 'ASC')

        ->setFirstResult(($page - 1) * $limit)
        ->setMaxResults($limit);

        return new Paginator($queryBuilder->getQuery());
    }

    public function qbForList(): QueryBuilder
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.campus', 'c')->addSelect('c')
            ->leftJoin('s.etat', 'e')->addSelect('e')
            ->leftJoin('s.organisateur', 'o')->addSelect('o');
    }


    public function findWithJointure(int $id) : ?Sortie{
        return $this->createQueryBuilder('s')
            ->addSelect('orga')
            ->addSelect('camp')
            ->addSelect('et')
            ->leftJoin('s.organisateur', 'orga')
            ->leftJoin('s.campus','camp')
            ->leftJoin('s.etat', 'et')
            ->where('s.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return Sortie[] Returns an array of Sortie objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Sortie
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


}
