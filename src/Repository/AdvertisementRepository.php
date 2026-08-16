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
            ->innerJoin('a.zonePlacements', 'zp')
            ->andWhere('zp.zoneKey = :zoneKey')
            ->andWhere('a.targetSpace = :space')
            ->andWhere('a.status = :status')
            ->andWhere('a.startAt <= :now')
            ->andWhere('a.endAt IS NULL OR a.endAt > :now')
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

    /** @return Advertisement[] */
    public function findFiltered(?string $zoneKey, ?string $status, ?string $term, string $sort = 'zoneKey', string $dir = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('a');

        if ($zoneKey) {
            $qb->andWhere('a.zoneKey = :zoneKey')->setParameter('zoneKey', $zoneKey);
        }
        if ($status) {
            $qb->andWhere('a.status = :status')->setParameter('status', $status);
        }
        if ($term) {
            $qb->andWhere('a.title LIKE :term')->setParameter('term', '%' . $term . '%');
        }

        // "dimension" et "clicks" sont calculés en PHP après coup (pas de vraie colonne SQL) — on trie ici par zoneKey par défaut dans ce cas, le contrôleur retrie ensuite en PHP.
        $sqlSortable = ['zoneKey', 'position', 'targetSpace', 'startAt', 'status', 'isPaid'];
        $sqlSort = in_array($sort, $sqlSortable, true) ? $sort : 'zoneKey';

        $qb->orderBy('a.' . $sqlSort, $dir);
        if ('zoneKey' !== $sqlSort) {
            $qb->addOrderBy('a.zoneKey', 'ASC');
        }

        return $qb->getQuery()->getResult();
    }

    public function expireOutdated(): void
    {
        $this->createQueryBuilder('a')
            ->update()
            ->set('a.status', ':expired')->setParameter('expired', 'expired')
            ->where('a.status = :active')->setParameter('active', 'active')
            ->andWhere('a.endAt IS NOT NULL')
            ->andWhere('a.endAt <= :now')->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    public function findOneActiveByZoneAndCategory(string $zoneKey, Category $category, string $targetSpace): ?Advertisement
    {
        $now = new \DateTimeImmutable();
        return $this->createQueryBuilder('a')
            ->andWhere('a.zoneKey = :zoneKey')
            ->andWhere('a.relatedCategory = :category')
            ->andWhere('a.targetSpace = :space')
            ->andWhere('a.status = :status')
            ->andWhere('a.startAt <= :now')
            ->andWhere('a.endAt IS NULL OR a.endAt > :now')
            ->setParameter('zoneKey', $zoneKey)
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
