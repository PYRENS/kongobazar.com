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

    /** Admin — tous les items (actifs ou non) d'un emplacement, dans l'ordre. */
    public function findAllByLocation(string $location, string $targetSpace = 'public'): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.location = :location')
            ->andWhere('m.targetSpace = :space')
            ->andWhere('m.parent IS NULL')
            ->setParameter('location', $location)
            ->setParameter('space', $targetSpace)
            ->orderBy('m.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNextPosition(string $location, string $targetSpace = 'public'): int
    {
        $max = $this->createQueryBuilder('m')
            ->select('MAX(m.position)')
            ->andWhere('m.location = :location')
            ->andWhere('m.targetSpace = :space')
            ->setParameter('location', $location)
            ->setParameter('space', $targetSpace)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $max) + 1;
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
