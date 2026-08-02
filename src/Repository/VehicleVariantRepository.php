<?php

namespace App\Repository;

use App\Entity\VehicleVariant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VehicleVariant>
 */
class VehicleVariantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehicleVariant::class);
    }

    /** @return VehicleVariant[] */
    public function findByModel(int $modelId): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.model = :modelId')
            ->setParameter('modelId', $modelId)
            ->orderBy('v.yearBegin', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByModel(int $modelId): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->andWhere('v.model = :modelId')
            ->setParameter('modelId', $modelId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}