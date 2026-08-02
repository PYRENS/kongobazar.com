<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\SellerProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function countByBrand(int $brandId): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.brand = :brandId')
            ->setParameter('brandId', $brandId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return Product[] */
    public function findByBrand(int $brandId, int $limit = 100): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.brand = :brandId')
            ->setParameter('brandId', $brandId)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findBestSellersInStock(int $limit = 8): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.variants', 'v')
            ->andWhere('v.quantity > 0')
            ->andWhere('p.status = :status')
            ->setParameter('status', 'active')
            ->groupBy('p.id')
            ->orderBy('p.salesCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findNewArrivals(int $limit = 8): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findActiveDeals(int $limit = 4): array
    {
        $now = new \DateTimeImmutable();
        return $this->createQueryBuilder('p')
            ->join('p.discountCampaigns', 'd')
            ->andWhere('d.status = :status')
            ->andWhere('d.startAt <= :now')
            ->andWhere('d.endAt > :now')
            ->setParameter('status', 'active')
            ->setParameter('now', $now)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

   /* public function findByCategorySort(Category $category, string $sort, int $limit = 4): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.category = :category')
            ->andWhere('p.status = :status')
            ->setParameter('category', $category)
            ->setParameter('status', 'active')
            ->setMaxResults($limit);

        match ($sort) {
            'new_arrivals' => $qb->orderBy('p.createdAt', 'DESC'),
            'featured' => $qb->andWhere('p.featured = true')->orderBy('p.createdAt', 'DESC'),
            default => $qb->orderBy('p.salesCount', 'DESC'), // best_sellers
        };

        return $qb->getQuery()->getResult();
    }*/

    public function findByCategorySort(array $categories, string $sort, int $limit = 4): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.category IN (:categories)')
            ->andWhere('p.status = :status')
            ->setParameter('categories', $categories)
            ->setParameter('status', 'active')
            ->setMaxResults($limit);

        match ($sort) {
            'new_arrivals' => $qb->orderBy('p.createdAt', 'DESC'),
            'featured' => $qb->andWhere('p.featured = true')->orderBy('p.createdAt', 'DESC'),
            default => $qb->orderBy('p.salesCount', 'DESC'),
        };

        return $qb->getQuery()->getResult();
    }


   /* public function findByCategoryForTrending(Category $category, int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.category = :category')
            ->andWhere('p.status = :status')
            ->setParameter('category', $category)
            ->setParameter('status', 'active')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }*/

    public function findByCategoryForTrending(array $categories, int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.category IN (:categories)')
            ->andWhere('p.status = :status')
            ->setParameter('categories', $categories)
            ->setParameter('status', 'active')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findTopSellingBySeller(SellerProfile $seller, int $limit = 4): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.sellerProfile = :seller')
            ->setParameter('seller', $seller)
            ->orderBy('p.salesCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }


    public function searchByTerm(string $term, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->andWhere('p.title LIKE :term OR p.reference LIKE :term')
            ->setParameter('status', 'active')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('p.salesCount', 'DESC');

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }


    public function findByMode(string $mode, ?int $categoryId, string $sort, ?int $limit = null, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', $mode === 'coming_soon' ? 'coming_soon' : 'active');

        match ($mode) {
            'on_sale' => $qb->andWhere('p.compareAtPrice IS NOT NULL'),
            'discount' => $qb->join('p.discountCampaigns', 'd')
                ->andWhere('d.status = :dStatus')
                ->andWhere('d.startAt <= :now')
                ->andWhere('d.endAt > :now')
                ->setParameter('dStatus', 'active')
                ->setParameter('now', new \DateTimeImmutable()),
            'individual' => $qb->join('p.sellerProfile', 's')
                ->andWhere('s INSTANCE OF App\Entity\IndividualProfile'),
            default => null, // 'coming_soon' déjà géré par le status ci-dessus
        };

        if ($categoryId) {
            $category = $this->getEntityManager()->getRepository(\App\Entity\Category::class)->find($categoryId);
            if ($category) {
                $qb->andWhere('p.category IN (:categories)')
                ->setParameter('categories', $category->getDescendantCategories());
            }
        }

        match ($sort) {
            'price_asc' => $qb->orderBy('p.basePrice', 'ASC'),
            'price_desc' => $qb->orderBy('p.basePrice', 'DESC'),
            default => $qb->orderBy('p.createdAt', 'DESC'),
        };

        if (null !== $limit) {
            $qb->setMaxResults($limit)->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }


    public function countByMode(string $mode, ?int $categoryId): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.status = :status')
            ->setParameter('status', $mode === 'coming_soon' ? 'coming_soon' : 'active');

        match ($mode) {
            'on_sale' => $qb->andWhere('p.compareAtPrice IS NOT NULL'),
            'discount' => $qb->join('p.discountCampaigns', 'd')
                ->andWhere('d.status = :dStatus')
                ->andWhere('d.startAt <= :now')
                ->andWhere('d.endAt > :now')
                ->setParameter('dStatus', 'active')
                ->setParameter('now', new \DateTimeImmutable()),
            'individual' => $qb->join('p.sellerProfile', 's')
                ->andWhere('s INSTANCE OF App\Entity\IndividualProfile'),
            default => null,
        };

        if ($categoryId) {
            $category = $this->getEntityManager()->getRepository(\App\Entity\Category::class)->find($categoryId);
            if ($category) {
                $qb->andWhere('p.category IN (:categories)')
                ->setParameter('categories', $category->getDescendantCategories());
            }
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findByCategoryAdmin(array $categoryIds, ?string $status, string $sort, ?string $term): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.category IN (:ids)')
            ->setParameter('ids', $categoryIds);

        if ($status) {
            $qb->andWhere('p.status = :status')->setParameter('status', $status);
        }
        if ($term) {
            $qb->andWhere('p.title LIKE :term')->setParameter('term', '%' . $term . '%');
        }

        match ($sort) {
            'price_asc' => $qb->orderBy('p.basePrice', 'ASC'),
            'price_desc' => $qb->orderBy('p.basePrice', 'DESC'),
            default => $qb->orderBy('p.createdAt', 'DESC'),
        };

        return $qb->setMaxResults(100)->getQuery()->getResult();
    }



    //    /**
    //     * @return Product[] Returns an array of Product objects
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

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
