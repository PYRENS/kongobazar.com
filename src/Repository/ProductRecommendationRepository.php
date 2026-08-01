<?php

namespace App\Repository;

use App\Entity\ProductRecommendation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductRecommendation>
 */
class ProductRecommendationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductRecommendation::class);
    }

    public function findByProduct(\App\Entity\Product $product): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.product = :product')
            ->setParameter('product', $product)
            ->orderBy('r.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findRecommendedProductsFor(\App\Entity\Product $product): array
    {
        $direct = $this->createQueryBuilder('r')
            ->andWhere('r.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->getResult();

        $reverse = $this->createQueryBuilder('r')
            ->andWhere('r.recommendedProduct = :product')
            ->andWhere('r.mutual = true')
            ->setParameter('product', $product)
            ->getQuery()
            ->getResult();

        $entries = [];
        foreach ($direct as $rec) {
            $entries[] = ['position' => $rec->getPosition() ?? 999, 'product' => $rec->getRecommendedProduct()];
        }
        foreach ($reverse as $rec) {
            $entries[] = ['position' => $rec->getPosition() ?? 999, 'product' => $rec->getProduct()];
        }

        usort($entries, fn ($a, $b) => $a['position'] <=> $b['position']);

        return array_map(fn ($e) => $e['product'], $entries);
    }

    //    /**
    //     * @return ProductRecommendation[] Returns an array of ProductRecommendation objects
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

    //    public function findOneBySomeField($value): ?ProductRecommendation
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
