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

    /** Enfants directs d'une catégorie (ou rayons racine si $parentId est null) ayant au moins un produit en stock, directement ou dans leurs descendants. */
    public function findChildrenWithInStockProducts(?int $parentId): array
    {
        // Catégories porteuses d'au moins un produit en stock (typiquement des feuilles)
        $leafIds = $this->getEntityManager()->createQueryBuilder()
            ->select('DISTINCT IDENTITY(p.category)')
            ->from(\App\Entity\Product::class, 'p')
            ->andWhere('p.quantity > 0')
            ->getQuery()
            ->getSingleColumnResult();

        if (empty($leafIds)) {
            return [];
        }

        // Remonte les ancêtres de chaque feuille pour marquer tous les niveaux parents comme "ayant du stock"
        $categoriesWithStock = [];
        foreach ($this->findBy(['id' => $leafIds]) as $leaf) {
            $current = $leaf;
            while ($current) {
                $categoriesWithStock[$current->getId()] = true;
                $current = $current->getParent();
            }
        }

        if (empty($categoriesWithStock)) {
            return [];
        }

        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.id IN (:ids)')
            ->setParameter('ids', array_keys($categoriesWithStock))
            ->orderBy('c.position', 'ASC');

        if ($parentId) {
            $qb->andWhere('c.parent = :parentId')->setParameter('parentId', $parentId);
        } else {
            $qb->andWhere('c.parent IS NULL');
        }

        return $qb->getQuery()->getResult();
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

    public function findPinnedTrending(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.trendingPinned = true')
            ->andWhere('c.active = true')
            ->orderBy('c.trendingPinnedPosition', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findMegaMenuRayons(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent IS NULL')
            ->andWhere('c.active = true')
            ->andWhere('c.megaMenuVisible = true')
            ->orderBy('c.megaMenuPosition', 'ASC')
            ->addOrderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllRootCategoriesForMegaMenuAdmin(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent IS NULL')
            ->orderBy('c.megaMenuPosition', 'ASC')
            ->addOrderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllRootCategoriesForTopCategoryAdmin(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent IS NULL')
            ->orderBy('c.topRayon', 'DESC')
            ->addOrderBy('c.topRayonPosition', 'ASC')
            ->addOrderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findMegaMenuFeaturedChildren(Category $rayon): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent = :rayon')
            ->andWhere('c.megaMenuChildFeatured = true')
            ->setParameter('rayon', $rayon)
            ->orderBy('c.megaMenuChildPosition', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findFlyoutFeaturedColumns(Category $rayon): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent = :rayon')
            ->andWhere('c.flyoutColumnFeatured = true')
            ->setParameter('rayon', $rayon)
            ->orderBy('c.flyoutColumnPosition', 'ASC')
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
