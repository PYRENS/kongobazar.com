<?php

namespace App\Service;

use App\Entity\IndividualSectionCategory;
use App\Repository\ProductRepository;

/** Résout une IndividualSectionCategory en liste de produits : prioritaires d'abord, complétés automatiquement. */
class IndividualSectionSelector
{
    public function __construct(private readonly ProductRepository $productRepository)
    {
    }

    /** @return \App\Entity\Product[] */
    public function select(IndividualSectionCategory $sectionCategory): array
    {
        $priorityItems = $sectionCategory->getPriorityProducts();
        $products = [];
        foreach ($priorityItems as $item) {
            if ('active' === $item->getProduct()->getStatus()) {
                $products[] = $item->getProduct();
            }
        }

        $remaining = $sectionCategory->getCardCount() - count($products);
        if ($remaining > 0) {
            $excludeIds = array_map(fn ($p) => $p->getId(), $products);
            $auto = $this->productRepository->findLatestByIndividualSellers(
                $sectionCategory->getCategory()->getDescendantCategories(),
                $remaining,
                $excludeIds
            );
            $products = array_merge($products, $auto);
        }

        return array_slice($products, 0, $sectionCategory->getCardCount());
    }
}
