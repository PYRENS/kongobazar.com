<?php

namespace App\Service;

use App\Entity\NewItemsTab;
use App\Entity\Product;
use App\Repository\ProductRepository;

/** Traduit un NewItemsTab en { bigCard: Product|null, smallCards: Product[] } pour l'accueil. */
class NewItemsTabSelector
{
    public function __construct(private readonly ProductRepository $productRepository)
    {
    }

    /** @return array{bigCard: ?Product, smallCards: Product[]} */
    public function select(NewItemsTab $tab): array
    {
        $bigCard = null;

        if ('targeted' === $tab->getMode()) {
            // Le produit coché (isBigCard) fait foi. Rien coché = aléatoire parmi la sélection.
            foreach ($tab->getTargetedProducts() as $item) {
                if ($item->isBigCard() && 'active' === $item->getProduct()->getStatus()) {
                    $bigCard = $item->getProduct();
                    break;
                }
            }
        }

        $products = 'targeted' === $tab->getMode()
            ? $this->selectTargeted($tab)
            : $this->selectAuto($tab);

        if (empty($products)) {
            return ['bigCard' => null, 'smallCards' => []];
        }

        if (!$bigCard) {
            $bigCard = $products[array_rand($products)];
        }

        $smallCards = array_values(array_filter($products, fn ($p) => $p->getId() !== $bigCard->getId()));

        return [
            'bigCard' => $bigCard,
            'smallCards' => array_slice($smallCards, 0, 8),
        ];
    }

    /** Nouveaux articles, hors vendeurs "Particulier". */
    private function selectAuto(NewItemsTab $tab): array
    {
        $category = $tab->getCategory();
        if (!$category) {
            return [];
        }

        return $this->productRepository->findNewArrivalsExcludingIndividuals(
            $category->getDescendantCategories(),
            $tab->getProductCount()
        );
    }

    /** Conserve l'ordre de sélection de l'admin — ne garde que les produits toujours actifs. */
    private function selectTargeted(NewItemsTab $tab): array
    {
        $result = [];
        foreach ($tab->getTargetedProducts() as $item) {
            if ('active' === $item->getProduct()->getStatus()) {
                $result[] = $item->getProduct();
            }
        }

        return array_slice($result, 0, $tab->getProductCount());
    }
}
