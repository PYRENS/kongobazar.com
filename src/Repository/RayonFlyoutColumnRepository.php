<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\RayonFlyoutColumn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RayonFlyoutColumnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RayonFlyoutColumn::class);
    }

    /** @return RayonFlyoutColumn[] */
    public function findByRayonOrdered(Category $rayon): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.rayon = :rayon')
            ->setParameter('rayon', $rayon)
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNextPosition(Category $rayon): int
    {
        $max = $this->createQueryBuilder('c')
            ->select('MAX(c.position)')
            ->andWhere('c.rayon = :rayon')
            ->setParameter('rayon', $rayon)
            ->getQuery()
            ->getSingleScalarResult();

        return ($max ?? -1) + 1;
    }
}
