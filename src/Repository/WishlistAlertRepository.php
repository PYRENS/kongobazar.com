<?php

namespace App\Repository;

use App\Entity\WishlistAlert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WishlistAlert>
 */
class WishlistAlertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WishlistAlert::class);
    }

    public function hasRecentAlert(\App\Entity\WishlistItem $item, string $type, int $hours = 24): bool
    {
        $since = new \DateTimeImmutable("-{$hours} hours");

        $count = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.wishlistItem = :item')
            ->andWhere('a.type = :type')
            ->andWhere('a.sentAt >= :since')
            ->setParameter('item', $item)
            ->setParameter('type', $type)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    //    /**
    //     * @return WishlistAlert[] Returns an array of WishlistAlert objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('w.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?WishlistAlert
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
