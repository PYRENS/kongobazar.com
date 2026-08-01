<?php

namespace App\Repository;

use App\Entity\ProductViewLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductViewLog>
 */
class ProductViewLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductViewLog::class);
    }

    public function findMostVisited(int $days = 7, int $limit = 8): array
    {
        $since = new \DateTimeImmutable("-{$days} days");

        $rows = $this->createQueryBuilder('v')
            ->select('IDENTITY(v.product) AS productId, COUNT(v.id) AS visitCount')
            ->andWhere('v.viewedAt >= :since')
            ->setParameter('since', $since)
            ->groupBy('v.product')
            ->orderBy('visitCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $productRepository = $this->getEntityManager()->getRepository(\App\Entity\Product::class);

        $results = [];
        foreach ($rows as $row) {
            $product = $productRepository->find($row['productId']);
            if (null !== $product) {
                $results[] = ['product' => $product, 'visitCount' => (int) $row['visitCount']];
            }
        }

        return $results;
    }


    //    /**
    //     * @return ProductViewLog[] Returns an array of ProductViewLog objects
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

    //    public function findOneBySomeField($value): ?ProductViewLog
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
