<?php

namespace App\Service;

use App\Entity\HotDealSectionSetting;
use App\Entity\Product;
use App\Repository\ProductRepository;

class HotDealSectionSelector
{
    public function __construct(private readonly ProductRepository $productRepository)
    {
    }

    /** @return Product[] */
    public function select(HotDealSectionSetting $settings): array
    {
        $limit = $settings->getDisplayCount();
        $activeDeals = $this->productRepository->findAllActiveDeals();
        $activeIds = array_map(fn (Product $p) => $p->getId(), $activeDeals);

        // Épinglés par l'admin — uniquement s'ils sont toujours réellement en vente flash active.
        $pinned = [];
        foreach ($settings->getTargetedProducts() as $product) {
            if (in_array($product->getId(), $activeIds, true)) {
                $pinned[] = $product;
            }
        }
        $pinned = array_slice($pinned, 0, $limit);
        $pinnedIds = array_map(fn (Product $p) => $p->getId(), $pinned);

        $remaining = $limit - count($pinned);
        if ($remaining <= 0) {
            return $pinned;
        }

        // Complément : ventes flash actives restantes, triées par % de réduction décroissant.
        $pool = array_values(array_filter($activeDeals, fn (Product $p) => !in_array($p->getId(), $pinnedIds, true)));
        usort($pool, fn (Product $a, Product $b) => ($b->getActiveDiscountPercent() ?? 0) <=> ($a->getActiveDiscountPercent() ?? 0));

        return array_merge($pinned, array_slice($pool, 0, $remaining));
    }
}
