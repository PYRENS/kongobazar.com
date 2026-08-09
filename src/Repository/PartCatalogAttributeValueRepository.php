<?php

namespace App\Repository;

use App\Entity\PartCatalogAttributeValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PartCatalogAttributeValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PartCatalogAttributeValue::class);
    }

    /** @return PartCatalogAttributeValue[] */
    public function findByEntry(int $entryId): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.partCatalogEntry = :entryId')
            ->setParameter('entryId', $entryId)
            ->getQuery()
            ->getResult();
    }
}