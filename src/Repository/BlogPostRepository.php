<?php

namespace App\Repository;

use App\Entity\BlogPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BlogPost>
 */
class BlogPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlogPost::class);
    }

    public function findLatestPublished(): ?BlogPost
    {
        $now = new \DateTimeImmutable();
        return $this->createQueryBuilder('b')
            ->andWhere('b.status = :status')
            ->andWhere('b.publishedAt <= :now')
            ->setParameter('status', 'published')
            ->setParameter('now', $now)
            ->orderBy('b.publishedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countFiltered(?string $term, ?string $status): int
    {
        return count($this->buildFilterQuery($term, $status)->getQuery()->getResult());
    }

    /** @return BlogPost[] */
    public function findFiltered(?string $term, ?string $status, string $sort, string $dir, int $page, int $perPage): array
    {
        $sortMap = ['title' => 'b.title', 'status' => 'b.status', 'publishedAt' => 'b.publishedAt', 'createdAt' => 'b.createdAt'];
        $orderBy = $sortMap[$sort] ?? 'b.createdAt';
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        return $this->buildFilterQuery($term, $status)
            ->orderBy($orderBy, $dir)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    private function buildFilterQuery(?string $term, ?string $status)
    {
        $qb = $this->createQueryBuilder('b');
        if ($term) {
            $qb->andWhere('b.title LIKE :term')->setParameter('term', '%' . $term . '%');
        }
        if ($status) {
            $qb->andWhere('b.status = :status')->setParameter('status', $status);
        }
        return $qb;
    }

    /** @return BlogPost[] */
    public function findRecentPublished(int $limit = 5): array
    {
        $now = new \DateTimeImmutable();
        return $this->createQueryBuilder('b')
            ->andWhere('b.status = :status')
            ->andWhere('b.publishedAt <= :now')
            ->setParameter('status', 'published')
            ->setParameter('now', $now)
            ->orderBy('b.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countPublished(?string $term): int
    {
        return count($this->buildPublicQuery($term)->getQuery()->getResult());
    }

    /** @return BlogPost[] */
    public function findPublishedPaginated(?string $term, int $page, int $perPage): array
    {
        return $this->buildPublicQuery($term)
            ->orderBy('b.publishedAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    private function buildPublicQuery(?string $term)
    {
        $now = new \DateTimeImmutable();
        $qb = $this->createQueryBuilder('b')
            ->andWhere('b.status = :status')
            ->andWhere('b.publishedAt <= :now')
            ->setParameter('status', 'published')
            ->setParameter('now', $now);

        if ($term) {
            $qb->andWhere('b.title LIKE :term')->setParameter('term', '%' . $term . '%');
        }

        return $qb;
    }

    //    /**
    //     * @return BlogPost[] Returns an array of BlogPost objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?BlogPost
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
