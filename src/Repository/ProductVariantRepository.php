<?php

namespace App\Repository;

use App\Entity\ProductVariant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductVariant>
 */
class ProductVariantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductVariant::class);
    }


    public function findByProductColorSize(\App\Entity\Product $product, ?int $colorId, ?int $sizeId): ?\App\Entity\ProductVariant
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.product = :product')
            ->setParameter('product', $product);

        if ($colorId) {
            $qb->andWhere('v.color = :colorId')->setParameter('colorId', $colorId);
        } else {
            $qb->andWhere('v.color IS NULL');
        }

        if ($sizeId) {
            $qb->andWhere('v.size = :sizeId')->setParameter('sizeId', $sizeId);
        } else {
            $qb->andWhere('v.size IS NULL');
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    //    /**
    //     * @return ProductVariant[] Returns an array of ProductVariant objects
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

    //    public function findOneBySomeField($value): ?ProductVariant
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
