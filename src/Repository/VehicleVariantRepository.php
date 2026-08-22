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

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->getQuery()
            ->getSingleScalarResult();
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

    public function countByBrand(int $brandId): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->join('v.model', 'm')
            ->andWhere('m.brand = :brandId')
            ->setParameter('brandId', $brandId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return VehicleVariant[] */
    public function findFiltered(?string $term = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->join('v.model', 'm')
            ->join('m.brand', 'b')
            ->orderBy('v.id', 'DESC');

        if ($term) {
            $qb->andWhere('v.name LIKE :term OR m.name LIKE :term OR b.name LIKE :term')
                ->setParameter('term', '%' . $term . '%');
        }

        return $qb->getQuery()->getResult();
    }
}