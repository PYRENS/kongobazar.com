<?php

namespace App\Service;

use App\Entity\NewItemsTab;
use App\Entity\Product;
use App\Repository\ProductRepository;

/**
 * Traduit un NewItemsTab en { bigCard: Product|null, products: Product[] } pour l'accueil.
 *
 * "products" est un pool complet (jusqu'à 3 pages), comme pour "Articles tendances" —
 * "bigCard" reste l'un des produits de ce pool (pas exclu), simplement repéré à part
 * pour que le template puisse le mettre en avant visuellement sur desktop.
 */
class NewItemsTabSelector
{
    private const PAGES_POOL_FACTOR = 3;

    public function __construct(private readonly ProductRepository $productRepository)
    {
    }

    /** @return array{bigCard: ?Product, products: Product[]} */
    public function select(NewItemsTab $tab): array
    {
        $perPage = max(1, $tab->getProductCount());
        $poolSize = $perPage * self::PAGES_POOL_FACTOR;

        $bigCard = null;
        if ('targeted' === $tab->getMode()) {
            foreach ($tab->getTargetedProducts() as $item) {
                if ($item->isBigCard() && 'active' === $item->getProduct()->getStatus()) {
                    $bigCard = $item->getProduct();
                    break;
                }
            }
        }

        $products = 'targeted' === $tab->getMode()
            ? $this->selectTargeted($tab, $poolSize)
            : $this->selectAuto($tab, $poolSize);

        if (empty($products)) {
            return ['bigCard' => null, 'products' => []];
        }

        if (!$bigCard) {
            $bigCard = $products[array_rand($products)];
        }

        return [
            'bigCard' => $bigCard,
            'products' => $products,
        ];
    }

    /** Nouveaux articles, hors vendeurs "Particulier". */
    private function selectAuto(NewItemsTab $tab, int $limit): array
    {
        $category = $tab->getCategory();
        if (!$category) {
            return [];
        }

        return $this->productRepository->findNewArrivalsExcludingIndividuals(
            $category->getDescendantCategories(),
            $limit
        );
    }

    /** Conserve l'ordre de sélection de l'admin — ne garde que les produits toujours actifs. */
    private function selectTargeted(NewItemsTab $tab, int $limit): array
    {
        $result = [];
        foreach ($tab->getTargetedProducts() as $item) {
            if ('active' === $item->getProduct()->getStatus()) {
                $result[] = $item->getProduct();
            }
        }

        return array_slice($result, 0, $limit);
    }
}