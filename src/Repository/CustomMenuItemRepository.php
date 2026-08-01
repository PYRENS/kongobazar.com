<?php

namespace App\Repository;

use App\Entity\CustomMenuItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomMenuItem>
 */
class CustomMenuItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomMenuItem::class);
    }

    public function findByLocationAndSpace(string $location, string $targetSpace): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.location = :location')
            ->andWhere('m.targetSpace = :space')
            ->andWhere('m.active = true')
            ->andWhere('m.parent IS NULL')
            ->setParameter('location', $location)
            ->setParameter('space', $targetSpace)
            ->orderBy('m.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return CustomMenuItem[] Returns an array of CustomMenuItem objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?CustomMenuItem
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
