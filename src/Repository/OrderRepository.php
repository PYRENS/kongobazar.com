<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * @param int[] $userIds
     * @return array<int, int> [userId => nombre de commandes passées en tant qu'acheteur]
     */
    public function countGroupedByBuyerIds(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $result = $this->createQueryBuilder('o')
            ->select('IDENTITY(o.buyer) as uid, COUNT(o.id) as total')
            ->andWhere('o.buyer IN (:ids)')
            ->setParameter('ids', $userIds)
            ->groupBy('o.buyer')
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($result as $row) {
            $map[(int) $row['uid']] = (int) $row['total'];
        }

        return $map;
    }

    //    /**
    //     * @return Order[] Returns an array of Order objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Order
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
