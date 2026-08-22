<?php

namespace App\Repository;

use App\Entity\VehicleModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VehicleModel>
 */
class VehicleModelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehicleModel::class);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByType(bool $moto): int
    {
        $qb = $this->createQueryBuilder('m')->select('COUNT(m.id)');
        $moto ? $qb->andWhere("m.type = 'moto'") : $qb->andWhere("m.type IS NULL OR m.type != 'moto'");

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /** @param bool $moto true = modèles Moto (type='moto'), false = modèles Auto (type IS NULL) */
    public function findByBrandAndType(int $brandId, bool $moto): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.brand = :brandId')
            ->setParameter('brandId', $brandId)
            ->orderBy('m.name', 'ASC');

        if ($moto) {
            $qb->andWhere("m.type = 'moto'");
        } else {
            $qb->andWhere('m.type IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /** @return VehicleModel[] */
    public function findByBrand(int $brandId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.brand = :brandId')
            ->setParameter('brandId', $brandId)
            ->orderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return VehicleModel[] */
    public function findFiltered(?int $brandId, bool $filterAuto, bool $filterMoto, ?string $term = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->orderBy('m.name', 'ASC');

        if ($brandId) {
            $qb->andWhere('m.brand = :brandId')->setParameter('brandId', $brandId);
        }

        // m.type = null => Auto par défaut, 'moto' => Moto
        if ($filterAuto && !$filterMoto) {
            $qb->andWhere('m.type IS NULL');
        } elseif ($filterMoto && !$filterAuto) {
            $qb->andWhere("m.type = 'moto'");
        }
        // les deux cochées, ou aucune : pas de filtre supplémentaire

        if ($term) {
            $qb->join('m.brand', 'b')
                ->andWhere('m.name LIKE :term OR m.name2 LIKE :term OR b.name LIKE :term')
                ->setParameter('term', '%' . $term . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function countByBrand(int $brandId): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.brand = :brandId')
            ->setParameter('brandId', $brandId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @param bool $moto true = modèles Moto (type='moto'), false = modèles Auto (type IS NULL) */
    public function countByBrandAndType(int $brandId, bool $moto): int
    {
        $qb = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.brand = :brandId')
            ->setParameter('brandId', $brandId);

        if ($moto) {
            $qb->andWhere("m.type = 'moto'");
        } else {
            $qb->andWhere('m.type IS NULL');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}