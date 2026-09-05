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

    /** @param array<string,bool> $includeTypes clés possibles : kbz, store, pro, individual */
    public function findMostVisited(int $days = 7, int $limit = 8, array $includeTypes = []): array
    {
        $since = new \DateTimeImmutable("-{$days} days");

        // On tire une marge plus large que $limit car le filtre par type de vendeur se fait ensuite en PHP.
        $rows = $this->createQueryBuilder('v')
            ->select('IDENTITY(v.product) AS productId, COUNT(v.id) AS visitCount')
            ->andWhere('v.viewedAt >= :since')
            ->setParameter('since', $since)
            ->groupBy('v.product')
            ->orderBy('visitCount', 'DESC')
            ->setMaxResults($limit * 6)
            ->getQuery()
            ->getResult();

        $productRepository = $this->getEntityManager()->getRepository(\App\Entity\Product::class);

        $results = [];
        foreach ($rows as $row) {
            $product = $productRepository->find($row['productId']);
            if (null === $product || 'active' !== $product->getStatus()) {
                continue;
            }

            if ($includeTypes) {
                $seller = $product->getSellerProfile();
                if (!$seller) {
                    continue;
                }
                $matches = ($includeTypes['kbz'] ?? true) && $seller->isKbz()
                    || ($includeTypes['store'] ?? true) && $seller instanceof \App\Entity\StoreProfile && !$seller->isKbz()
                    || ($includeTypes['pro'] ?? true) && $seller instanceof \App\Entity\ProProfile
                    || ($includeTypes['individual'] ?? true) && $seller instanceof \App\Entity\IndividualProfile;
                if (!$matches) {
                    continue;
                }
            }

            $results[] = ['product' => $product, 'visitCount' => (int) $row['visitCount']];
            if (count($results) >= $limit) {
                break;
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
