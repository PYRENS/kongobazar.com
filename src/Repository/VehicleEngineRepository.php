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

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAutoAll(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.variant IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countMotoAll(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.model IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
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

    public function countByFuelType(int $fuelTypeId): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.fuelType = :fuelTypeId')
            ->setParameter('fuelTypeId', $fuelTypeId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countWithoutFuelType(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.fuelType IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return array<int, array{name: string, total: int}> Répartition du nombre de motorisations par énergie */
    public function getBreakdownByFuelType(): array
    {
        $rows = $this->createQueryBuilder('e')
            ->select('f.name as name, COUNT(e.id) as total')
            ->join('e.fuelType', 'f')
            ->groupBy('f.id')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map(fn ($r) => ['name' => $r['name'], 'total' => (int) $r['total']], $rows);
    }
}