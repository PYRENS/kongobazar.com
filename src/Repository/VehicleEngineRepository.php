<?php

namespace App\Repository;

use App\Entity\VehicleEngine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VehicleEngine>
 */
class VehicleEngineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehicleEngine::class);
    }

    /** @return VehicleEngine[] */
    public function findByVariant(int $variantId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.variant = :variantId')
            ->setParameter('variantId', $variantId)
            ->orderBy('e.yearStart', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return VehicleEngine[] */
    public function findByModel(int $modelId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.model = :modelId')
            ->setParameter('modelId', $modelId)
            ->orderBy('e.yearStart', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Motorisations rattachées directement au modèle (cas Moto). */
    public function countByModel(int $modelId): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.model = :modelId')
            ->setParameter('modelId', $modelId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Motorisations agrégées via les variantes du modèle (cas Auto). */
    public function countByModelViaVariants(int $modelId): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->join('e.variant', 'v')
            ->andWhere('v.model = :modelId')
            ->setParameter('modelId', $modelId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}