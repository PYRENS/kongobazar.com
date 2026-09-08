<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\TrendingTabSetting;
use App\Repository\ProductRepository;

/**
 * Traduit un TrendingTabSetting (un onglet) en une liste concrète de produits à afficher.
 *
 * Les "produits ciblés" (mis en avant par l'admin) sont toujours prioritaires, quel que
 * soit le mode choisi — le reste des places se complète automatiquement via ce mode
 * (récents / meilleures ventes / aléatoire). On récupère un pool plus large que
 * productCount pour permettre plusieurs pages en façade (navigation par flèches).
 */
class TrendingTabSelector
{
    private const PAGES_POOL_FACTOR = 3; // jusqu'à 3 pages de productCount produits

    public function __construct(private readonly ProductRepository $productRepository)
    {
    }

    /** @return Product[] */
    public function select(TrendingTabSetting $tab): array
    {
        $category = $tab->getCategory();
        if (!$category) {
            return [];
        }

        $categoryIds = [$category->getId()];
        foreach ($category->getDescendantCategories() as $descendant) {
            $categoryIds[] = $descendant->getId();
        }

        $perPage = max(1, $tab->getProductCount());
        $poolSize = $perPage * self::PAGES_POOL_FACTOR;

        // Produits mis en avant par l'admin — toujours en tête, quel que soit le mode.
        $pinned = [];
        foreach ($tab->getTargetedProducts() as $product) {
            if ('active' === $product->getStatus()) {
                $pinned[] = $product;
            }
        }
        $pinned = array_slice($pinned, 0, $poolSize);
        $pinnedIds = array_map(fn (Product $p) => $p->getId(), $pinned);

        $remaining = $poolSize - count($pinned);
        if ($remaining <= 0) {
            return $pinned;
        }

        $filler = match ($tab->getMode()) {
            'best_sellers' => $this->productRepository->findByCategorySort($category->getDescendantCategories(), 'best_sellers', $remaining, false, $pinnedIds),
            'random' => $this->selectRandom($categoryIds, $remaining, $pinnedIds),
            default => $this->productRepository->findByCategorySort($category->getDescendantCategories(), 'new_arrivals', $remaining, false, $pinnedIds), // 'recent' et 'targeted' (fallback)
        };

        return array_merge($pinned, $filler);
    }

    private function selectRandom(array $categoryIds, int $limit, array $excludeIds = []): array
    {
        $products = $this->productRepository->findActiveByCategoryIds($categoryIds);
        $products = array_values(array_filter($products, fn (Product $p) => !in_array($p->getId(), $excludeIds, true)));
        shuffle($products);
        return array_slice($products, 0, $limit);
    }
}