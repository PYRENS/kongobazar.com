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

    /**
     * @return Product[] Produits (pièces) compatibles avec une motorisation, filtrés/triés/paginés.
     */
    public function findCompatibleWithEngine(int $engineId, ?string $term, ?int $categoryId, ?array $brandIds, ?string $condition, string $sort, string $dir, int $page, int $perPage, ?array $locationUnitIds = null, ?float $priceMin = null, ?float $priceMax = null): array
    {
        $qb = $this->buildCompatibleWithEngineQuery($engineId, $term, $categoryId, $brandIds, $condition, $locationUnitIds, $priceMin, $priceMax);
        $dirSql = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        match ($sort) {
            'basePrice' => $qb->orderBy('p.basePrice', $dirSql),
            'salesCount' => $qb->orderBy('p.salesCount', $dirSql),
            'title' => $qb->orderBy('p.title', $dirSql),
            default => $qb->orderBy('p.createdAt', 'DESC'),
        };

        return $qb->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function countCompatibleWithEngine(int $engineId, ?string $term, ?int $categoryId, ?array $brandIds, ?string $condition, ?array $locationUnitIds = null, ?float $priceMin = null, ?float $priceMax = null): int
    {
        return (int) $this->buildCompatibleWithEngineQuery($engineId, $term, $categoryId, $brandIds, $condition, $locationUnitIds, $priceMin, $priceMax)
            ->select('COUNT(DISTINCT p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
    /** @return string[] Titres de toutes les pièces compatibles (non paginé), pour l'autocomplétion. */
    public function findCompatibleWithEngineTitles(int $engineId): array
    {
        $rows = $this->buildCompatibleWithEngineQuery($engineId, null, null, null, null)
            ->select('DISTINCT p.title')
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'title');
    }

    private function buildCompatibleWithEngineQuery(int $engineId, ?string $term, ?int $categoryId, ?array $brandIds, ?string $condition, ?array $locationUnitIds = null, ?float $priceMin = null, ?float $priceMax = null)
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.partListingDetails', 'pld')
            ->join('pld.engineCompatibilities', 'pec')
            ->leftJoin('pld.partCatalogEntry', 'pce')
            ->andWhere('pec.vehicleEngine = :engineId')
            ->andWhere('p.status = :status')
            ->setParameter('engineId', $engineId)
            ->setParameter('status', 'active');

        if ($term) {
            $conditions = $qb->expr()->orX(
                $qb->expr()->like('p.title', ':term'),
                $qb->expr()->like('p.ean', ':term'),
                $qb->expr()->like('p.reference', ':term'),
                $qb->expr()->like('pld.ean', ':term'),
                $qb->expr()->like('pld.manufacturerRef', ':term'),
                $qb->expr()->like('pld.oemCodes', ':term'),
                $qb->expr()->like('pce.ean', ':term'),
                $qb->expr()->like('pce.manufacturerRef', ':term')
            );
            $qb->setParameter('term', '%' . $term . '%');

            // Réf. KongoBazar (ex: "KBZ-000042" ou juste "42") — se traduit en recherche par ID.
            if (preg_match('/(\d{1,10})/', $term, $m)) {
                $numericId = (int) ltrim($m[1], '0');
                if ($numericId > 0) {
                    $conditions->add($qb->expr()->eq('p.id', ':termId'));
                    $qb->setParameter('termId', $numericId);
                }
            }

            $qb->andWhere($conditions);
        }
        if ($categoryId) {
            $qb->andWhere('p.category = :categoryId')->setParameter('categoryId', $categoryId);
        }
        if ($brandIds) {
            $qb->andWhere('p.brand IN (:brandIds)')->setParameter('brandIds', $brandIds);
        }
        if ($condition) {
            $qb->andWhere('p.condition = :condition')->setParameter('condition', $condition);
        }
        if ($locationUnitIds) {
            $qb->join('p.sellerProfile', 'sp')
                ->join('sp.user', 'sellerUser')
                ->andWhere('sellerUser.administrativeUnit IN (:locationUnits)')
                ->setParameter('locationUnits', $locationUnitIds);
        }
        if (null !== $priceMin) {
            $qb->andWhere('p.basePrice >= :priceMin')->setParameter('priceMin', $priceMin);
        }
        if (null !== $priceMax) {
            $qb->andWhere('p.basePrice <= :priceMax')->setParameter('priceMax', $priceMax);
        }

        return $qb;
    }

    /** @return array<int, array{id: int, name: string}> Fabricants réellement présents parmi les pièces compatibles (facette, sans le filtre fabricant lui-même). */
    public function getAvailableBrandsForEngine(int $engineId, ?string $term, ?int $categoryId, ?string $condition, ?array $locationUnitIds = null, ?float $priceMin = null, ?float $priceMax = null): array
    {
        $rows = $this->buildCompatibleWithEngineQuery($engineId, $term, $categoryId, null, $condition, $locationUnitIds, $priceMin, $priceMax)
            ->join('p.brand', 'facetBrand')
            ->select('DISTINCT facetBrand.id as id, facetBrand.name as name')
            ->orderBy('facetBrand.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(fn ($r) => ['id' => (int) $r['id'], 'name' => $r['name']], $rows);
    }

    /** Prix le plus élevé parmi les pièces compatibles (filtres actifs SAUF le prix lui-même) — sert de plafond au spinner. */
    public function getMaxPriceForEngine(int $engineId, ?string $term, ?int $categoryId, ?array $brandIds, ?string $condition, ?array $locationUnitIds = null): float
    {
        $result = $this->buildCompatibleWithEngineQuery($engineId, $term, $categoryId, $brandIds, $condition, $locationUnitIds)
            ->select('MAX(p.basePrice)')
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== null ? (float) $result : 0.0;
    }

    /**
     * @return array<int, array{id: int, name: string, total: int}> Nombre de pièces compatibles par catégorie
     *         (facette — calculée sans le filtre catégorie lui-même, pour permettre le changement de catégorie).
     */
    public function getCategoryFacetsForEngine(int $engineId, ?string $term, ?array $brandIds, ?string $condition, ?array $locationUnitIds = null): array
    {
        $qb = $this->buildCompatibleWithEngineQuery($engineId, $term, null, $brandIds, $condition, $locationUnitIds)
            ->join('p.category', 'facetCat')
            ->select('facetCat.id as id, facetCat.name as name, COUNT(DISTINCT p.id) as total')
            ->groupBy('facetCat.id')
            ->orderBy('facetCat.name', 'ASC');

        $rows = $qb->getQuery()->getResult();

        return array_map(fn ($r) => ['id' => (int) $r['id'], 'name' => $r['name'], 'total' => (int) $r['total']], $rows);
    }

    /** @return Product[] */
    public function findFiltered(?string $term, ?array $categoryIds, ?string $status, ?string $condition, string $sort, string $dir, int $page, int $perPage, ?int $sellerProfileId = null): array
    {
        $qb = $this->buildFilterQuery($term, $categoryIds, $status, $condition, $sellerProfileId);
        $dirSql = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        // leftJoin + addSelect : précharge la 1ère image en une seule requête,
        // au lieu d'une requête séparée par ligne affichée (N+1).
        $qb->leftJoin('p.images', 'img')->addSelect('img');

        switch ($sort) {
            case 'kongobazarReference':
                $qb->orderBy('p.id', $dirSql);
                break;
            case 'category':
                $qb->leftJoin('p.category', 'sortCat')->orderBy('sortCat.name', $dirSql);
                break;
            case 'brand':
                $qb->leftJoin('p.brand', 'sortBrand')->orderBy('sortBrand.name', $dirSql);
                break;
            case 'seller':
                $qb->leftJoin('p.sellerProfile', 'sortSp')->leftJoin('sortSp.user', 'sortUser')->orderBy('sortUser.email', $dirSql);
                break;
            case 'basePrice':
            case 'title':
            case 'createdAt':
            case 'salesCount':
            case 'status':
            case 'condition':
                $qb->orderBy('p.' . $sort, $dirSql);
                break;
            default:
                $qb->orderBy('p.createdAt', 'DESC');
        }

        $query = $qb->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery();

        // Paginator Doctrine obligatoire ici : avec une jointure "un-vers-plusieurs"
        // (p.images), setFirstResult/setMaxResults nu limite les LIGNES SQL
        // (produit × image), pas les produits distincts — un produit avec
        // plusieurs images peut alors "manger" la place d'un autre produit
        // sur la page, coupant la liste avant d'avoir atteint le vrai total.
        return iterator_to_array(new \Doctrine\ORM\Tools\Pagination\Paginator($query, true));
    }

    public function countFiltered(?string $term, ?array $categoryIds, ?string $status, ?string $condition, ?int $sellerProfileId = null): int
    {
        return (int) $this->buildFilterQuery($term, $categoryIds, $status, $condition, $sellerProfileId)
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param int[] $sellerProfileIds
     * @return array<int, int> [sellerProfileId => nombre de produits] pour un statut donné
     */
    public function countGroupedBySellerProfileIds(array $sellerProfileIds, string $status): array
    {
        if (empty($sellerProfileIds)) {
            return [];
        }

        $result = $this->createQueryBuilder('p')
            ->select('IDENTITY(p.sellerProfile) as spId, COUNT(p.id) as total')
            ->andWhere('p.sellerProfile IN (:ids)')
            ->andWhere('p.status = :status')
            ->setParameter('ids', $sellerProfileIds)
            ->setParameter('status', $status)
            ->groupBy('p.sellerProfile')
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($result as $row) {
            $map[(int) $row['spId']] = (int) $row['total'];
        }

        return $map;
    }

    private function buildFilterQuery(?string $term, ?array $categoryIds, ?string $status, ?string $condition, ?int $sellerProfileId = null)
    {
        $qb = $this->createQueryBuilder('p');

        if ($term) {
            $qb->andWhere('p.title LIKE :term OR p.reference LIKE :term')->setParameter('term', '%' . $term . '%');
        }
        if ($categoryIds) {
            $qb->andWhere('p.category IN (:categoryIds)')->setParameter('categoryIds', $categoryIds);
        }
        if ($status) {
            $qb->andWhere('p.status = :status')->setParameter('status', $status);
        }
        if ($condition) {
            $qb->andWhere('p.condition = :condition')->setParameter('condition', $condition);
        }
        if ($sellerProfileId) {
            $qb->andWhere('p.sellerProfile = :sellerProfileId')->setParameter('sellerProfileId', $sellerProfileId);
        }

        return $qb;
    }

    /** @param int[] $categoryIds */
    public function findByCategoryScope(array $categoryIds, ?string $term, ?string $status, ?string $condition, int $limit = 15, int $offset = 0): array
    {
        $qb = $this->buildScopeQuery($categoryIds, $term, $status, $condition);

        return $qb->orderBy('p.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @param int[] $categoryIds */
    public function countByCategoryScope(array $categoryIds, ?string $term, ?string $status, ?string $condition): int
    {
        return (int) $this->buildScopeQuery($categoryIds, $term, $status, $condition)
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @param int[] $categoryIds */
    private function buildScopeQuery(array $categoryIds, ?string $term, ?string $status, ?string $condition)
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.category IN (:categoryIds)')->setParameter('categoryIds', $categoryIds);

        if ($term) {
            $qb->andWhere('p.title LIKE :term OR p.reference LIKE :term')->setParameter('term', '%' . $term . '%');
        }
        if ($status) {
            $qb->andWhere('p.status = :status')->setParameter('status', $status);
        }
        if ($condition) {
            $qb->andWhere('p.condition = :condition')->setParameter('condition', $condition);
        }

        return $qb;
    }

    /** @return Product[] */
    public function searchByCategoryAndTerm(int $categoryId, string $term, ?int $excludeId = null, int $limit = 15): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.partListingDetails', 'pld')
            ->andWhere('p.category = :categoryId')->setParameter('categoryId', $categoryId)
            ->setMaxResults($limit);

        $termConditions = $qb->expr()->orX(
            $qb->expr()->like('p.title', ':term'),
            $qb->expr()->like('p.reference', ':term'),
            $qb->expr()->like('p.ean', ':term'),
            $qb->expr()->like('pld.manufacturerRef', ':term'),
            $qb->expr()->like('pld.ean', ':term')
        );
        $qb->setParameter('term', '%' . $term . '%');

        // Référence KongoBazar (KBZ-000042, calculée depuis l'ID) — extrait le numéro et matche sur p.id
        if (preg_match('/(\d+)/', $term, $m)) {
            $kbzId = (int) ltrim($m[1], '0');
            if ($kbzId > 0) {
                $termConditions->add($qb->expr()->eq('p.id', ':kbzId'));
                $qb->setParameter('kbzId', $kbzId);
            }
        }

        $qb->andWhere($termConditions);

        if ($excludeId) {
            $qb->andWhere('p.id != :excludeId')->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
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

    private function buildBrandFilterQuery(int $brandId, ?string $status, ?string $term)
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.brand = :brandId')
            ->setParameter('brandId', $brandId);

        if ($status) {
            $qb->andWhere('p.status = :status')->setParameter('status', $status);
        }

        if ($term) {
            $qb->andWhere('p.title LIKE :term OR p.reference LIKE :term')
                ->setParameter('term', '%' . $term . '%');
        }

        return $qb;
    }

    /** @return Product[] */
    public function findFilteredByBrand(int $brandId, ?string $status, ?string $term, int $page, int $perPage, string $sort = 'createdAt', string $dir = 'DESC'): array
    {
        $qb = $this->buildBrandFilterQuery($brandId, $status, $term);
        $dirSql = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        switch ($sort) {
            case 'kongobazarReference':
                $qb->orderBy('p.id', $dirSql);
                break;
            case 'category':
                $qb->leftJoin('p.category', 'sortCat')->orderBy('sortCat.name', $dirSql);
                break;
            case 'seller':
                $qb->leftJoin('p.sellerProfile', 'sortSp')->leftJoin('sortSp.user', 'sortUser')->orderBy('sortUser.email', $dirSql);
                break;
            case 'stock':
                $qb->leftJoin('p.variants', 'sortVar')
                    ->addSelect('COALESCE(SUM(sortVar.quantity), 0) as HIDDEN stockSum')
                    ->groupBy('p.id')
                    ->orderBy('stockSum', $dirSql);
                break;
            case 'title':
            case 'basePrice':
            case 'condition':
            case 'status':
            case 'salesCount':
            case 'createdAt':
                $qb->orderBy('p.' . $sort, $dirSql);
                break;
            default:
                $qb->orderBy('p.createdAt', 'DESC');
        }

        return $qb->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function countFilteredByBrand(int $brandId, ?string $status, ?string $term): int
    {
        return (int) $this->buildBrandFilterQuery($brandId, $status, $term)
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
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

    public function findInStockByCategory(int $categoryId, int $limit = 50): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.category = :categoryId')
            ->andWhere('p.quantity > 0')
            ->setParameter('categoryId', $categoryId)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchInStockByTerm(string $term, int $limit = 15): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.title LIKE :term')
            ->andWhere('p.quantity > 0')
            ->setParameter('term', '%' . $term . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
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

    public function findByCategoryAdmin(array $categoryIds, ?string $status, string $sort, ?string $term, int $page = 1, int $perPage = 20): array
    {
        $qb = $this->buildCategoryAdminQuery($categoryIds, $status, $term);

        match ($sort) {
            'price_asc' => $qb->orderBy('p.basePrice', 'ASC'),
            'price_desc' => $qb->orderBy('p.basePrice', 'DESC'),
            default => $qb->orderBy('p.createdAt', 'DESC'),
        };

        return $qb->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function countByCategoryAdmin(array $categoryIds, ?string $status, ?string $term): int
    {
        return (int) $this->buildCategoryAdminQuery($categoryIds, $status, $term)
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Statistiques globales de la catégorie, indépendantes des filtres actifs. */
    public function getCategoryAdminStats(array $categoryIds): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('COUNT(p.id) as total')
            ->addSelect('SUM(CASE WHEN p.status = \'active\' THEN 1 ELSE 0 END) as activeCount')
            ->addSelect('SUM(CASE WHEN p.status = \'suspended\' THEN 1 ELSE 0 END) as blockedCount')
            ->addSelect('SUM(p.salesCount) as totalSold')
            ->andWhere('p.category IN (:ids)')
            ->setParameter('ids', $categoryIds)
            ->getQuery()
            ->getOneOrNullResult();

        return [
            'total' => (int) ($result['total'] ?? 0),
            'activeCount' => (int) ($result['activeCount'] ?? 0),
            'blockedCount' => (int) ($result['blockedCount'] ?? 0),
            'totalSold' => (int) ($result['totalSold'] ?? 0),
        ];
    }

    private function buildCategoryAdminQuery(array $categoryIds, ?string $status, ?string $term)
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

        return $qb;
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
