<?php

namespace App\Service;

use App\Entity\Campaign;
use App\Repository\ProductRepository;

/**
 * Liste ordonnée des IDs de produits à afficher en premier sur la page d'une campagne :
 *  1. les produits choisis à la main (dans l'ordre de l'admin),
 *  2. puis, pour chaque boutique/Pro prioritaire, ses N produits soldés les plus récents.
 * Chaque volet peut être désactivé séparément dans l'admin de la campagne.
 */
class SalePriorityResolver
{
    public function __construct(private ProductRepository $productRepository)
    {
    }

    /** @return int[] */
    public function resolve(?Campaign $campaign): array
    {
        if (!$campaign) {
            return [];
        }

        $ids = [];
        if ($campaign->isPriorityProductsEnabled()) {
            foreach ($campaign->getPriorityProducts() as $item) {
                $ids[$item->getProduct()->getId()] = true;
            }
        }
        if ($campaign->isPrioritySellersEnabled()) {
            foreach ($campaign->getPrioritySellers() as $item) {
                $sellerIds = $this->productRepository->findSaleProductIdsBySeller(
                    $item->getSellerProfile()->getId(),
                    $item->getProductLimit()
                );
                foreach ($sellerIds as $id) {
                    $ids[$id] = true;
                }
            }
        }

        return array_keys($ids);
    }
}