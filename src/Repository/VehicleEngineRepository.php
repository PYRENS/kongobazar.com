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

    /** @return VehicleEngine[] */
    public function findByModelViaVariants(int $modelId): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.variant', 'v')
            ->andWhere('v.model = :modelId')
            ->setParameter('modelId', $modelId)
            ->orderBy('e.yearStart', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return VehicleEngine[] */
    public function findFiltered(?string $term = null): array
    {
        $qb = $this->createQueryBuilder('e')->orderBy('e.id', 'DESC');

        if ($term) {
            $qb->andWhere('e.brandNameCache LIKE :term OR e.modelNameCache LIKE :term OR e.variantNameCache LIKE :term OR e.label LIKE :term')
                ->setParameter('term', '%' . $term . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function countByVariant(int $variantId): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.variant = :variantId')
            ->setParameter('variantId', $variantId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Motorisations Auto d'une marque (via variante -> modèle -> marque). */
    public function countAutoByBrand(int $brandId): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->join('e.variant', 'v')
            ->join('v.model', 'm')
            ->andWhere('m.brand = :brandId')
            ->setParameter('brandId', $brandId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Motorisations Moto d'une marque (rattachées directement au modèle). */
    public function countMotoByBrand(int $brandId): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->join('e.model', 'm')
            ->andWhere('m.brand = :brandId')
            ->setParameter('brandId', $brandId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}