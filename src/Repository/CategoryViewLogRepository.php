<?php

namespace App\Repository;

use App\Entity\CategoryViewLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategoryViewLog>
 */
class CategoryViewLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategoryViewLog::class);
    }

    public function findMostVisited(int $days = 7, int $limit = 8): array
    {
        $since = new \DateTimeImmutable("-{$days} days");

        $rows = $this->createQueryBuilder('v')
            ->select('IDENTITY(v.category) AS categoryId, COUNT(v.id) AS visitCount')
            ->andWhere('v.viewedAt >= :since')
            ->setParameter('since', $since)
            ->groupBy('v.category')
            ->orderBy('visitCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $categoryRepository = $this->getEntityManager()->getRepository(\App\Entity\Category::class);

        $results = [];
        foreach ($rows as $row) {
            $category = $categoryRepository->find($row['categoryId']);
            if (null !== $category) {
                $results[] = ['category' => $category, 'visitCount' => (int) $row['visitCount']];
            }
        }

        return $results;
    }

    //    /**
    //     * @return CategoryViewLog[] Returns an array of CategoryViewLog objects
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

    //    public function findOneBySomeField($value): ?CategoryViewLog
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
