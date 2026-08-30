<?php

namespace App\Service;

use App\Entity\HomeDealsSetting;
use App\Entity\Product;
use App\Entity\SellerProfile;
use App\Entity\StoreProfile;
use App\Entity\ProProfile;
use App\Repository\ProductRepository;

/** Traduit la configuration HomeDealsSetting en une liste concrète de produits à afficher sur l'accueil. */
class HomeDealsSelector
{
    public function __construct(private readonly ProductRepository $productRepository)
    {
    }

    /** @return Product[] */
    public function select(HomeDealsSetting $settings): array
    {
        return match ($settings->getDisplayMode()) {
            'kbz_only' => $this->selectKbzOnly($settings),
            'mixed' => $this->selectMixed($settings),
            'targeted_stores' => $this->selectTargetedStores($settings),
            'targeted_products' => $this->selectTargetedProducts($settings),
            'category' => $this->selectCategory($settings),
            default => $this->selectRandom($settings),
        };
    }

    /** @return Product[] — toutes les ventes flash actives, hors exclusions globales (type et vendeurs précis). */
    private function eligibleDeals(HomeDealsSetting $settings): array
    {
        $deals = $this->productRepository->findAllActiveDeals();
        $excludedIds = array_map(fn (SellerProfile $s) => $s->getId(), $settings->getExcludedSellers()->toArray());

        return array_values(array_filter($deals, function (Product $product) use ($settings, $excludedIds) {
            $seller = $product->getSellerProfile();
            if (!$seller) {
                return false;
            }
            if (in_array($seller->getId(), $excludedIds, true)) {
                return false;
            }
            if ($settings->isExcludeBoutique() && $seller instanceof StoreProfile) {
                return false;
            }
            if ($settings->isExcludePro() && $seller instanceof ProProfile) {
                return false;
            }
            return true;
        }));
    }

    private function selectRandom(HomeDealsSetting $settings): array
    {
        $deals = $this->eligibleDeals($settings);
        shuffle($deals);
        return array_slice($deals, 0, $settings->getDisplayCount());
    }

    private function selectKbzOnly(HomeDealsSetting $settings): array
    {
        $deals = array_values(array_filter(
            $this->eligibleDeals($settings),
            fn (Product $p) => $p->getSellerProfile() && $p->getSellerProfile()->isKbz()
        ));
        shuffle($deals);
        return array_slice($deals, 0, $settings->getDisplayCount());
    }

    /** Répartition KBZ / autres avec compensation automatique si un groupe est en manque. */
    private function selectMixed(HomeDealsSetting $settings): array
    {
        $deals = $this->eligibleDeals($settings);
        $kbzDeals = array_values(array_filter($deals, fn (Product $p) => $p->getSellerProfile() && $p->getSellerProfile()->isKbz()));
        $otherDeals = array_values(array_filter($deals, fn (Product $p) => $p->getSellerProfile() && !$p->getSellerProfile()->isKbz()));
        shuffle($kbzDeals);
        shuffle($otherDeals);

        $wantKbz = $settings->getMixedKbzCount() ?? 0;
        $wantOther = $settings->getMixedOtherCount() ?? 0;

        $takenKbz = array_slice($kbzDeals, 0, $wantKbz);
        $takenOther = array_slice($otherDeals, 0, $wantOther);

        // Compensation : si un groupe est en manque, on comble avec l'excédent de l'autre.
        $missingKbz = $wantKbz - count($takenKbz);
        if ($missingKbz > 0) {
            $extra = array_slice($otherDeals, count($takenOther), $missingKbz);
            $takenOther = array_merge($takenOther, $extra);
        }
        $missingOther = $wantOther - count($takenOther);
        if ($missingOther > 0) {
            $extra = array_slice($kbzDeals, count($takenKbz), $missingOther);
            $takenKbz = array_merge($takenKbz, $extra);
        }

        $result = array_merge($takenKbz, $takenOther);
        shuffle($result);

        return array_slice($result, 0, $settings->getDisplayCount());
    }

    private function selectTargetedStores(HomeDealsSetting $settings): array
    {
        $targetedIds = array_map(fn (SellerProfile $s) => $s->getId(), $settings->getTargetedSellers()->toArray());
        if (!$targetedIds) {
            return [];
        }

        $deals = array_values(array_filter(
            $this->eligibleDeals($settings),
            fn (Product $p) => $p->getSellerProfile() && in_array($p->getSellerProfile()->getId(), $targetedIds, true)
        ));
        shuffle($deals);
        return array_slice($deals, 0, $settings->getDisplayCount());
    }

    /** Conserve l'ordre de sélection de l'admin (pas de mélange) — ne garde que les produits toujours réellement en vente flash active. */
    private function selectTargetedProducts(HomeDealsSetting $settings): array
    {
        $activeIds = array_map(fn (Product $p) => $p->getId(), $this->productRepository->findAllActiveDeals());

        $result = [];
        foreach ($settings->getTargetedProducts() as $product) {
            if (in_array($product->getId(), $activeIds, true)) {
                $result[] = $product;
            }
        }

        return array_slice($result, 0, $settings->getDisplayCount());
    }

    /** Catégories choisies (descendants inclus, même principe que resolveCategoryIdsWithDescendants ailleurs dans l'admin). */
    private function selectCategory(HomeDealsSetting $settings): array
    {
        $categoryIds = [];
        foreach ($settings->getTargetedCategories() as $category) {
            $categoryIds[] = $category->getId();
            foreach ($category->getDescendantCategories() as $descendant) {
                $categoryIds[] = $descendant->getId();
            }
        }
        if (!$categoryIds) {
            return [];
        }

        $deals = array_values(array_filter(
            $this->eligibleDeals($settings),
            fn (Product $p) => $p->getCategory() && in_array($p->getCategory()->getId(), $categoryIds, true)
        ));
        shuffle($deals);
        return array_slice($deals, 0, $settings->getDisplayCount());
    }
}
