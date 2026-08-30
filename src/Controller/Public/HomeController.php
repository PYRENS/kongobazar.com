<?php

namespace App\Controller\Public;

use App\Repository\AdvertisementRepository;
use App\Repository\BlogPostRepository;
use App\Repository\BrandRepository;
use App\Repository\CartRepository;
use App\Repository\CategoryRepository;
use App\Repository\CategoryViewLogRepository;
use App\Repository\CustomMenuItemRepository;
use App\Repository\ProductRepository;
use App\Repository\ProductViewLogRepository;
use App\Repository\ReviewRepository;
use App\Repository\SellerProfileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'public_home', host: 'kongobazar.com')]
    public function index(
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        AdvertisementRepository $advertisementRepository,
        \App\Service\AdZonePicker $adZonePicker,
        BrandRepository $brandRepository,
        BlogPostRepository $blogPostRepository,
        CustomMenuItemRepository $customMenuItemRepository,
        SellerProfileRepository $sellerProfileRepository,
        ReviewRepository $reviewRepository,
        CategoryViewLogRepository $categoryViewLogRepository,
        ProductViewLogRepository $productViewLogRepository,
        CartRepository $cartRepository,
        \App\Service\SeoResolver $seoResolver,
        \App\Service\HomeDealsSelector $homeDealsSelector,
        \App\Repository\HomeDealsSettingRepository $homeDealsSettingRepository,
        \App\Service\TrendingTabSelector $trendingTabSelector,
        \App\Repository\TrendingTabSettingRepository $trendingTabSettingRepository,
        \App\Repository\TrendingSectionSettingRepository $trendingSectionSettingRepository,
        \App\Repository\HomeCategoryBlockSettingRepository $homeCategoryBlockSettingRepository,
        \App\Repository\HomeCategoryBlockSectionSettingRepository $homeCategoryBlockSectionSettingRepository,
        \App\Repository\TopCategoryItemRepository $topCategoryItemRepository,
        \App\Repository\TopCategorySectionSettingRepository $topCategorySectionSettingRepository,
    ): Response {
        $rootCategories = $categoryRepository->findRootCategories();

        // --- Header ---
        $user = $this->getUser();
        $cart = $user ? $cartRepository->findOneBy(['user' => $user]) : null;
        // TODO : résolution du panier invité via jeton de session, une fois CartService construit

        // --- Hero ---
        $heroSlides = $advertisementRepository->findActiveByZone('homepage_hero_main', 'public');
        $adZonePicker->recordImpressions($heroSlides, 'homepage_hero_main');
        $sideAdTop = $adZonePicker->pick('homepage_hero_side_top', 'public');
        $sideAdBottom = $adZonePicker->pick('homepage_hero_side_bottom', 'public');

        // --- Colonne gauche ---
        $adSidebarTop = $adZonePicker->pick('sidebar_top', 'public');
        $adSidebarMiddle = $adZonePicker->pick('sidebar_middle', 'public');
        $bestSellers = $productRepository->findBestSellersInStock(8);
        $newArrivals = $productRepository->findNewArrivals(8);
        $latestPost = $blogPostRepository->findLatestPublished();

        // --- Centre : promo + deals ---
        $promoStrip = $adZonePicker->pick('homepage_promo_strip', 'public');
        $homeDealsSettings = $homeDealsSettingRepository->getSingleton();
        $dealsProducts = $homeDealsSelector->select($homeDealsSettings);
        $centerAdBanner = $adZonePicker->pick('homepage_center_banner', 'public');

        // --- Articles tendances ---
        $trendingSectionSettings = $trendingSectionSettingRepository->getSingleton();
        $trendingTabs = $trendingTabSettingRepository->findAllOrdered();
        $trendingTabCategories = array_map(fn ($tab) => $tab->getCategory(), $trendingTabs);
        $trendingProductsByCategory = [];
        foreach ($trendingTabs as $tab) {
            $trendingProductsByCategory[$tab->getCategory()->getSlug()] = $trendingTabSelector->select($tab);
        }

        // --- Blocs catégorie complets ---
        $categoryBlockSectionSettings = $homeCategoryBlockSectionSettingRepository->getSingleton();
        $blockIcons = ['bi-cpu', 'bi-bag-heart', 'bi-house-door', 'bi-collection'];
        $categoryBlocks = $homeCategoryBlockSettingRepository->findAllOrdered();
        $featuredBlocks = [];
        foreach ($categoryBlocks as $i => $block) {
            $category = $block->getCategory();
            $sortTabs = $block->getSortTabs();
            $firstVisibleTab = null;
            foreach ($sortTabs as $tab) {
                if ($tab->isVisible()) {
                    $firstVisibleTab = $tab;
                    break;
                }
            }
            $initialSort = $firstVisibleTab ? $firstVisibleTab->getSortKey() : 'best_sellers';
            $initialCount = $firstVisibleTab ? $firstVisibleTab->getProductCount() : 4;
            $firstSubcategoryItem = $block->getSubcategoryItems()->first();
            $initialScopeCategory = $firstSubcategoryItem ? $firstSubcategoryItem->getCategory() : $category;

            $featuredBlocks[] = [
                'block' => $block,
                'category' => $category,
                'icon' => $blockIcons[$i % count($blockIcons)],
                'subcategories' => $block->getSubcategoryItems(),
                'sortTabs' => $sortTabs,
                'initialProducts' => $productRepository->findByCategorySort($initialScopeCategory->getDescendantCategories(), $initialSort, $initialCount),
                'banner' => $advertisementRepository->findOneActiveByZoneAndCategory('category_block_banner', $category, 'public'),
            ];
        }

        // --- Top Catégories / Top Vendeur ---
        $topCategorySectionSettings = $topCategorySectionSettingRepository->getSingleton();
        $topCategories = $topCategoryItemRepository->findAllOrdered();
        $topVendorsRaw = $sellerProfileRepository->findTopVendors(4);
        $topVendors = array_map(function ($vendor) use ($productRepository, $reviewRepository) {
            $vendor->averageRating = $reviewRepository->getAverageRatingForSeller($vendor);
            $vendor->salesCount = array_sum(array_map(
                fn ($p) => $p->getSalesCount(),
                $vendor->getProducts()->toArray()
            ));
            $vendor->topProducts = $productRepository->findTopSellingBySeller($vendor, 4);
            return $vendor;
        }, $topVendorsRaw);

        // --- Popular Tags ---
        $featuredBrands = $brandRepository->findFeaturedHomepage();
        $adLifestyleLeft = $adZonePicker->pick('homepage_lifestyle_left', 'public');
        $adLifestyleCenter = $adZonePicker->pick('homepage_lifestyle_center', 'public');
        $adLifestyleRight = $adZonePicker->pick('homepage_lifestyle_right', 'public');

        // --- Most Viewed ---
        $mostViewedProducts = $productViewLogRepository->findMostVisited(7, 8);

        // --- Tendances (bandeau du header) ---
        $trendingCategories = $categoryViewLogRepository->findMostVisited(7, 8);

        // --- Menu personnalisé header/footer ---
        $customMenuHeader = $customMenuItemRepository->findByLocationAndSpace('header_main', 'public');
        $footerColumns = [];
        for ($i = 1; $i <= 8; $i++) {
            $footerColumns["footer_col_{$i}"] = $customMenuItemRepository->findByLocationAndSpace("footer_col_{$i}", 'public');
        }
        $footerSocialAd = $adZonePicker->pick('footer_social_banner', 'public');
        $footerBrands = $brandRepository->findFeaturedHomepage(); // réutilisé pour le bandeau de mots-clés
        $footerMosaicAds = $advertisementRepository->findActiveByZone('footer_mosaic', 'public'); // plusieurs photos
        $adZonePicker->recordImpressions($footerMosaicAds, 'footer_mosaic');
        $footerCallUsPhoto = $adZonePicker->pick('footer_callus_photo', 'public');
        $footerBottomLinks = $customMenuItemRepository->findByLocationAndSpace('footer_bottom_links', 'public');

        $seoData = $seoResolver->resolve('static_page', null, 'homepage', [
            'metaTitle' => 'KongoBazar — La marketplace de référence en RDC',
            'metaDescription' => 'KongoBazar — la marketplace de référence en RDC',
        ]);

        return $this->render('public/home.html.twig', [
            'seoData' => $seoData,
            'dealsEnabled' => $homeDealsSettings->isEnabled(),
            'trendingEnabled' => $trendingSectionSettings->isEnabled(),
            'categoryBlocksEnabled' => $categoryBlockSectionSettings->isEnabled(),
            'topCategoriesEnabled' => $topCategorySectionSettings->isEnabled(),
            'heroSlides' => $heroSlides,
            'sideAdTop' => $sideAdTop,
            'sideAdBottom' => $sideAdBottom,
            'adSidebarTop' => $adSidebarTop,
            'adSidebarMiddle' => $adSidebarMiddle,
            'bestSellers' => $bestSellers,
            'newArrivals' => $newArrivals,
            'latestPost' => $latestPost,
            'promoStrip' => $promoStrip,
            'dealsProducts' => $dealsProducts,
            'centerAdBanner' => $centerAdBanner,
            'trendingTabCategories' => $trendingTabCategories,
            'trendingProductsByCategory' => $trendingProductsByCategory,
            'featuredBlocks' => $featuredBlocks,
            'topCategories' => $topCategories,
            'topVendors' => $topVendors,
            'featuredBrands' => $featuredBrands,
            'adLifestyleLeft' => $adLifestyleLeft,
            'adLifestyleCenter' => $adLifestyleCenter,
            'adLifestyleRight' => $adLifestyleRight,
            'mostViewedProducts' => $mostViewedProducts,
            'footerColumns' => $footerColumns,
            'footerSocialAd' => $footerSocialAd,
            'footerBrands' => $footerBrands,
            'footerMosaicAds' => $footerMosaicAds,
            'footerCallUsPhoto' => $footerCallUsPhoto,
            'footerBottomLinks' => $footerBottomLinks,
        ]);
    }
}