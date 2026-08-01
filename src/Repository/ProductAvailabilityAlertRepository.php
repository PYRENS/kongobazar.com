<?php

namespace App\Repository;

use App\Entity\ProductAvailabilityAlert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductAvailabilityAlert>
 */
class ProductAvailabilityAlertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductAvailabilityAlert::class);
    }

    
    public function findPendingForProduct(\App\Entity\Product $product): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.product = :product')
            ->andWhere('a.notifiedAt IS NULL')
            ->setParameter('product', $product)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return ProductAvailabilityAlert[] Returns an array of ProductAvailabilityAlert objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ProductAvailabilityAlert
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
