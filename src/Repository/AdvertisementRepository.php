<?php

namespace App\Repository;

use App\Entity\Advertisement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Category;

/**
 * @extends ServiceEntityRepository<Advertisement>
 */
class AdvertisementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Advertisement::class);
    }

    public function findActiveByZone(string $zoneKey, string $targetSpace, ?int $limit = null): array
    {
        $now = new \DateTimeImmutable();
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.zoneKey = :zoneKey')
            ->andWhere('a.targetSpace = :space')
            ->andWhere('a.status = :status')
            ->andWhere('a.startAt <= :now')
            ->andWhere('a.endAt > :now')
            ->setParameter('zoneKey', $zoneKey)
            ->setParameter('space', $targetSpace)
            ->setParameter('status', 'active')
            ->setParameter('now', $now)
            ->orderBy('a.position', 'ASC');

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function findOneActiveByZone(string $zoneKey, string $targetSpace): ?Advertisement
    {
        $results = $this->findActiveByZone($zoneKey, $targetSpace, 1);
        return $results[0] ?? null;
    }

    public function findOneActiveByCategory(Category $category, string $targetSpace): ?Advertisement
    {
        $now = new \DateTimeImmutable();
        return $this->createQueryBuilder('a')
            ->andWhere('a.relatedCategory = :category')
            ->andWhere('a.targetSpace = :space')
            ->andWhere('a.status = :status')
            ->andWhere('a.startAt <= :now')
            ->andWhere('a.endAt > :now')
            ->setParameter('category', $category)
            ->setParameter('space', $targetSpace)
            ->setParameter('status', 'active')
            ->setParameter('now', $now)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return Advertisement[] Returns an array of Advertisement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Advertisement
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
