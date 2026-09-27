<?php

namespace App\Repository;

use App\Entity\ProductReview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductReview::class);
    }

    /** Version simple, pour un seul produit (utilisée par la fonction Twig product_review_stats()). */
    public function getStatsForProduct(int $productId): array
    {
        $stats = $this->getStatsForProducts([$productId]);
        return $stats[$productId] ?? ['average' => 0.0, 'count' => 0];
    }

    /**
     * Moyenne et nombre d'avis publiés, pour une liste de produits en une seule requête
     *
     * @param int[] $productIds
     * @return array<int, array{average: float, count: int}>
     */
    public function getStatsForProducts(array $productIds): array
    {
        if (!$productIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.product) AS productId', 'AVG(r.rating) AS average', 'COUNT(r.id) AS reviewCount')
            ->andWhere('r.product IN (:ids)')->setParameter('ids', $productIds)
            ->andWhere('r.status = :status')->setParameter('status', ProductReview::STATUS_PUBLISHED)
            ->groupBy('r.product')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($rows as $row) {
            $stats[(int) $row['productId']] = [
                'average' => round((float) $row['average'], 1),
                'count' => (int) $row['reviewCount'],
            ];
        }

        return $stats;
    }
}