<?php

namespace App\Service;

use App\Entity\SellerProfile;
use App\Entity\TopVendorSetting;
use App\Repository\ProductRepository;
use App\Repository\ReviewRepository;
use App\Repository\SellerProfileRepository;
use App\Repository\TopVendorTargetedSellerRepository;

/** Traduit un TopVendorSetting en une liste concrète de vendeurs (avec leurs produits vedettes) pour l'accueil. */
class TopVendorSelector
{
    public function __construct(
        private readonly SellerProfileRepository $sellerProfileRepository,
        private readonly ProductRepository $productRepository,
        private readonly ReviewRepository $reviewRepository,
        private readonly TopVendorTargetedSellerRepository $targetedSellerRepository,
    ) {
    }

    /** @return array<int, array{seller: SellerProfile, averageRating: float, salesCount: int, topProducts: array}> */
    public function select(TopVendorSetting $settings): array
    {
        $sellers = 'targeted' === $settings->getDisplayMode()
            ? $this->selectTargeted($settings)
            : $this->sellerProfileRepository->findAutoTopVendors($settings->getDisplayCount(), $settings->isExcludePro(), $settings->isExcludeBoutique());

        return array_map(fn (SellerProfile $seller) => [
            'seller' => $seller,
            'averageRating' => $this->reviewRepository->getAverageRatingForSeller($seller),
            'salesCount' => array_sum(array_map(fn ($p) => $p->getSalesCount(), $seller->getProducts()->toArray())),
            'topProducts' => $this->selectTopProductsForSeller($seller),
        ], $sellers);
    }

    /** @return SellerProfile[] */
    private function selectTargeted(TopVendorSetting $settings): array
    {
        $items = $this->targetedSellerRepository->findBySettingOrdered($settings);
        $sellers = array_map(fn ($item) => $item->getSeller(), $items);

        return array_slice($sellers, 0, $settings->getDisplayCount());
    }

    /** Priorité : produits "Vedette" choisis par le vendeur lui-même, puis complété par ses meilleures ventes. */
    private function selectTopProductsForSeller(SellerProfile $seller, int $limit = 4): array
    {
        $featured = array_values($this->productRepository->createQueryBuilder('p')
            ->andWhere('p.sellerProfile = :seller')
            ->andWhere('p.featured = true')
            ->andWhere('p.status = :status')
            ->setParameter('seller', $seller)
            ->setParameter('status', 'active')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult());

        if (count($featured) >= $limit) {
            return array_slice($featured, 0, $limit);
        }

        $bestSelling = $this->productRepository->findTopSellingBySeller($seller, $limit);
        $featuredIds = array_map(fn ($p) => $p->getId(), $featured);
        $fill = array_filter($bestSelling, fn ($p) => !in_array($p->getId(), $featuredIds, true));

        return array_slice(array_merge($featured, $fill), 0, $limit);
    }
}
