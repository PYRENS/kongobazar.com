<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\TrendingTabSetting;
use App\Repository\ProductRepository;

/** Traduit un TrendingTabSetting (un onglet) en une liste concrète de produits à afficher. */
class TrendingTabSelector
{
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

        return match ($tab->getMode()) {
            'best_sellers' => $this->productRepository->findByCategorySort($category->getDescendantCategories(), 'best_sellers', $tab->getProductCount()),
            'random' => $this->selectRandom($categoryIds, $tab->getProductCount()),
            'targeted' => $this->selectTargeted($tab),
            default => $this->productRepository->findByCategorySort($category->getDescendantCategories(), 'new_arrivals', $tab->getProductCount()), // 'recent'
        };
    }

    private function selectRandom(array $categoryIds, int $limit): array
    {
        $products = $this->productRepository->findActiveByCategoryIds($categoryIds);
        shuffle($products);
        return array_slice($products, 0, $limit);
    }

    /** Conserve l'ordre de sélection de l'admin — ne garde que les produits toujours actifs. */
    private function selectTargeted(TrendingTabSetting $tab): array
    {
        $result = [];
        foreach ($tab->getTargetedProducts() as $product) {
            if ('active' === $product->getStatus()) {
                $result[] = $product;
            }
        }

        return array_slice($result, 0, $tab->getProductCount());
    }
}
