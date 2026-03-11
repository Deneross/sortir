<?php

namespace App\Repository;

use App\Entity\Campus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Campus>
 */
class CampusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Campus::class);
    }


    public function getOnlyCampusNames(): array
    {
        $dql = 'SELECT c.name FROM App\Entity\Campus c';
        $query = $this->getEntityManager()->createQuery($dql);
        return $query->getResult();
    }

    public function findCampusWithFilters(string $nameForFilter)
    {
        return $this->createQueryBuilder('c')
            ->where('c.name LIKE :name')
            ->setParameter('name', '%' . $nameForFilter . '%')
            ->getQuery()
            ->getResult();
    }
}
