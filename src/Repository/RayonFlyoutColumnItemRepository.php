<?php

namespace App\Repository;

use App\Entity\RayonFlyoutColumn;
use App\Entity\RayonFlyoutColumnItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RayonFlyoutColumnItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RayonFlyoutColumnItem::class);
    }

    /** @return RayonFlyoutColumnItem[] */
    public function findByColumnOrdered(RayonFlyoutColumn $column): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.column = :column')
            ->setParameter('column', $column)
            ->orderBy('i.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNextPosition(RayonFlyoutColumn $column): int
    {
        $max = $this->createQueryBuilder('i')
            ->select('MAX(i.position)')
            ->andWhere('i.column = :column')
            ->setParameter('column', $column)
            ->getQuery()
            ->getSingleScalarResult();

        return ($max ?? -1) + 1;
    }
}
