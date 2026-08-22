<?php

namespace App\Repository;

use App\Entity\FuelType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FuelType>
 */
class FuelTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FuelType::class);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.active = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return FuelType[] */
    public function findFiltered(?string $term, string $sort, string $dir): array
    {
        $allowedSort = ['name', 'active'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'name';
        }
        $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';

        $qb = $this->createQueryBuilder('f')->orderBy('f.' . $sort, $dir);

        if ($term) {
            $qb->andWhere('f.name LIKE :term OR f.description LIKE :term')
                ->setParameter('term', '%' . $term . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /** @return FuelType[] */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.active = true')
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}