<?php

namespace App\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Sortie;
use Doctrine\ORM\QueryBuilder;

final class SortieExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    private const array ALLOWED_STATUSES = [2, 3, 4]; // Ouverte, Clôturée, En cours

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->addFilters($queryBuilder, $resourceClass, $queryNameGenerator);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->addFilters($queryBuilder, $resourceClass, $queryNameGenerator);
    }

    private function addFilters(
        QueryBuilder $queryBuilder,
        string $resourceClass,
        QueryNameGeneratorInterface $queryNameGenerator
    ): void
    {
        if ($resourceClass !== Sortie::class) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        // Paramètres uniques pour éviter les conflits
        $allowedStatesParam = $queryNameGenerator->generateParameterName('allowedStates');

        // JOIN sur l'entité Etat pour filtrer par libelle
        $queryBuilder
            ->andWhere(sprintf('IDENTITY(%s.etat) IN (:%s)', $alias, $allowedStatesParam))
            ->setParameter($allowedStatesParam, self::ALLOWED_STATUSES);
    }
}
