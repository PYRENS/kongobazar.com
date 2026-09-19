<?php

namespace App\Controller\Public;

use App\Repository\CampaignRepository;
use App\Repository\ProductRepository;
use App\Service\AdZonePicker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SoldeCampaignController extends AbstractController
{
    #[Route('/accueil-solde', name: 'home_solde', host: 'kongobazar.com')]
    public function index(
        CampaignRepository $campaignRepository,
        ProductRepository $productRepository,
        \App\Repository\TopCategoryItemRepository $topCategoryItemRepository,
        \App\Repository\TopCategorySectionSettingRepository $topCategorySectionSettingRepository,
    ): Response {
        $campaign = $campaignRepository->findCurrentlyLive();
        if (!$campaign || 'solde' !== $campaign->getType()) {
            return $this->redirectToRoute('home_index');
        }

        $filters = $productRepository->getSaleFilterOptions();
        $products = $productRepository->findSaleProductsBatch(null, null, null, null, $campaign->getBatchSize(), 0);
        $totalCount = $productRepository->countSaleProducts(null, null, null, null);

        $topCategorySectionSettings = $topCategorySectionSettingRepository->getSingleton();

        return $this->render('public/home_solde.html.twig', [
            'campaign' => $campaign,
            'products' => $products,
            'hasMore' => count($products) < $totalCount,
            'nextOffset' => count($products),
            'filters' => $filters,
            'topCategoriesEnabled' => $topCategorySectionSettings->isEnabled(),
            'topCategories' => $topCategoryItemRepository->findAllOrdered(),
            'topVendorEnabled' => false,
            'topVendors' => [],
        ]);
    }

    #[Route('/accueil-solde/produits', name: 'home_solde_load_more', host: 'kongobazar.com', methods: ['GET'])]
    public function loadMore(
        Request $request,
        CampaignRepository $campaignRepository,
        ProductRepository $productRepository,
        AdZonePicker $adZonePicker,
    ): Response {
        $campaign = $campaignRepository->findCurrentlyLive();
        $batchSize = $campaign ? $campaign->getBatchSize() : 12;

        $offset = max(0, (int) $request->query->get('offset', 0));
        $categoryId = $request->query->get('category') ? (int) $request->query->get('category') : null;
        $brandId = $request->query->get('brand') ? (int) $request->query->get('brand') : null;
        $minPrice = $request->query->get('min_price') !== null && $request->query->get('min_price') !== '' ? (float) $request->query->get('min_price') : null;
        $maxPrice = $request->query->get('max_price') !== null && $request->query->get('max_price') !== '' ? (float) $request->query->get('max_price') : null;

        $products = $productRepository->findSaleProductsBatch($categoryId, $brandId, $minPrice, $maxPrice, $batchSize, $offset);
        $totalCount = $productRepository->countSaleProducts($categoryId, $brandId, $minPrice, $maxPrice);
        $newOffset = $offset + count($products);

        $ad = $adZonePicker->pick('solde_between_batches', 'public');

        return $this->render('public/_partials/_solde_products_batch.html.twig', [
            'products' => $products,
            'ad' => $ad,
            'hasMore' => $newOffset < $totalCount,
            'nextOffset' => $newOffset,
        ]);
    }
}
