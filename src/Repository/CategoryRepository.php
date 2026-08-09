<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findRootCategories(?int $excludeId = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.parent IS NULL')
            ->orderBy('c.position', 'ASC');

        if ($excludeId) {
            $qb->andWhere('c.id != :excludeId')->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }

    public function findFeaturedHomepageTabs(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.featuredHomepageTab = true')
            ->orderBy('c.featuredHomepagePosition', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findFeaturedHomepageBlocks(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.featuredHomepageBlock = true')
            ->orderBy('c.featuredHomepageBlockPosition', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findTopCategoriesIllustrated(int $limit = 8): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.imageName IS NOT NULL')
            ->orderBy('c.position', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchByTerm(string $term, int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.name LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findTopRayons(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.topRayon = true')
            ->orderBy('c.topRayonPosition', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findChildrenOf(?int $parentId, ?int $excludeId = null): array
    {
        $qb = $this->createQueryBuilder('c')->orderBy('c.position', 'ASC');

        if ($parentId) {
            $qb->andWhere('c.parent = :parentId')->setParameter('parentId', $parentId);
        } else {
            $qb->andWhere('c.parent IS NULL');
        }

        if ($excludeId) {
            $qb->andWhere('c.id != :excludeId')->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }

    public function countChildrenOf(int $categoryId): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.parent = :categoryId')
            ->setParameter('categoryId', $categoryId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Catégories sans enfants uniquement — les seules où on peut réellement publier un produit. */
    public function findLeaves(): array
    {
        $sub = $this->createQueryBuilder('p2')
            ->select('IDENTITY(p2.parent)')
            ->where('p2.parent IS NOT NULL')
            ->distinct();

        return $this->createQueryBuilder('c')
            ->andWhere($this->getEntityManager()->createQueryBuilder()->expr()->notIn('c.id', $sub->getDQL()))
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function searchByName(string $term): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.name LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }

    public function countProductsIn(array $categoryIds): int
    {
        if (empty($categoryIds)) {
            return 0;
        }
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(\App\Entity\Product::class, 'p')
            ->andWhere('p.category IN (:ids)')
            ->setParameter('ids', $categoryIds)
            ->getQuery()
            ->getSingleScalarResult();
    }

    //    /**
    //     * @return Category[] Returns an array of Category objects
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

    //    public function findOneBySomeField($value): ?Category
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
