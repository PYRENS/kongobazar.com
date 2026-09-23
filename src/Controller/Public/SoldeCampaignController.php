<?php

namespace App\Controller\Public;

use App\Repository\CampaignRepository;
use App\Repository\ProductRepository;
use App\Repository\TopCategoryItemRepository;
use App\Repository\TopCategorySectionSettingRepository;
use App\Service\AdZonePicker;
use App\Service\SalePriorityResolver;
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
        TopCategoryItemRepository $topCategoryItemRepository,
        TopCategorySectionSettingRepository $topCategorySectionSettingRepository,
        SalePriorityResolver $priorityResolver,
    ): Response {
        $campaign = $campaignRepository->findCurrentlyLive();
        if (!$campaign) {
            return $this->redirectToRoute('public_home');
        }

        $priorityIds = $priorityResolver->resolve($campaign);

        $filters = $productRepository->getSaleFilterOptions();
        $products = $productRepository->findSaleProductsBatch(null, null, null, null, $campaign->getBatchSize(), 0, $priorityIds);
        $totalCount = $productRepository->countSaleProducts(null, null, null, null);

        // Top Catégories : uniquement celles (ou dont une sous-catégorie) qui contiennent un produit soldé.
        $saleCategoryIds = array_flip($productRepository->getSaleCategoryIdsWithAncestors());
        $topCategories = array_values(array_filter(
            $topCategoryItemRepository->findAllOrdered(),
            static fn ($item) => $item->getCategory() && isset($saleCategoryIds[$item->getCategory()->getId()])
        ));

        $topCategorySectionSettings = $topCategorySectionSettingRepository->getSingleton();

        return $this->render('public/home_solde.html.twig', [
            'campaign' => $campaign,
            'products' => $products,
            'hasMore' => count($products) < $totalCount,
            'nextOffset' => count($products),
            'filters' => $filters,
            'topCategoriesEnabled' => $campaign->isTopCategoriesEnabled() && $topCategorySectionSettings->isEnabled() && count($topCategories) > 0,
            'topCategories' => $topCategories,
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
        SalePriorityResolver $priorityResolver,
    ): Response {
        $campaign = $campaignRepository->findCurrentlyLive();
        $batchSize = $campaign ? $campaign->getBatchSize() : 12;
        $priorityIds = $priorityResolver->resolve($campaign);

        $offset = max(0, (int) $request->query->get('offset', 0));
        $categoryId = $request->query->get('category') ? (int) $request->query->get('category') : null;
        $brandId = $request->query->get('brand') ? (int) $request->query->get('brand') : null;
        $minPrice = $request->query->get('min_price') !== null && $request->query->get('min_price') !== '' ? (float) $request->query->get('min_price') : null;
        $maxPrice = $request->query->get('max_price') !== null && $request->query->get('max_price') !== '' ? (float) $request->query->get('max_price') : null;

        $products = $productRepository->findSaleProductsBatch($categoryId, $brandId, $minPrice, $maxPrice, $batchSize, $offset, $priorityIds);
        $totalCount = $productRepository->countSaleProducts($categoryId, $brandId, $minPrice, $maxPrice);
        $newOffset = $offset + count($products);

        // Numéro du lot demandé : 1 = deuxième lot de la page (le premier n'a jamais de bannière)
        $batchIndex = $batchSize > 0 ? intdiv($offset, $batchSize) : 0;
        $ad = $campaign ? $adZonePicker->pickForCampaign($campaign, 'solde_between_batches', $batchIndex) : null;

        return $this->render('public/_partials/_solde_products_batch.html.twig', [
            'products' => $products,
            'ad' => $ad,
            'cardModel' => $campaign ? $campaign->getCardModel() : 'classic',
            'campaignType' => $campaign ? $campaign->getType() : 'solde',
            'campaignEnd' => $campaign?->getEndAt(),
            'hasMore' => $newOffset < $totalCount,
            'nextOffset' => $newOffset,
        ]);
    }
}