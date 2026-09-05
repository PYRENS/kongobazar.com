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
        \App\Repository\MostViewedSettingRepository $mostViewedSettingRepository,
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
        \App\Service\TopVendorSelector $topVendorSelector,
        \App\Repository\TopVendorSettingRepository $topVendorSettingRepository,
        \App\Service\NewItemsTabSelector $newItemsTabSelector,
        \App\Repository\NewItemsTabRepository $newItemsTabRepository,
        \App\Repository\NewItemsSectionSettingRepository $newItemsSectionSettingRepository,
        \App\Repository\ComingSoonSectionSettingRepository $comingSoonSectionSettingRepository,
        \App\Repository\ComingSoonTabRepository $comingSoonTabRepository,
        \App\Repository\IndividualSectionSettingRepository $individualSectionSettingRepository,
        \App\Repository\IndividualSectionCategoryRepository $individualSectionCategoryRepository,
        \App\Service\IndividualSectionSelector $individualSectionSelector,
        \App\Repository\SponsorBrandRepository $sponsorBrandRepository,
        \App\Repository\PartnerRepository $partnerRepository,
        \App\Repository\SponsorSectionSettingRepository $sponsorSectionSettingRepository,
        \App\Repository\PartnerSectionSettingRepository $partnerSectionSettingRepository,
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
        $categoryBlocks = array_filter($homeCategoryBlockSettingRepository->findAllOrdered(), fn ($block) => $block->isEnabled());
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
                'initialProducts' => $productRepository->findByCategorySort($initialScopeCategory->getDescendantCategories(), $initialSort, $initialCount, $block->isIndividualSellersOnly()),
                'banner' => $advertisementRepository->findOneActiveByZoneAndCategory('category_block_banner', $category, 'public'),
            ];
        }

        // --- Top Catégories / Top Vendeur ---
        $topCategorySectionSettings = $topCategorySectionSettingRepository->getSingleton();
        $topCategories = $topCategoryItemRepository->findAllOrdered();
        $topVendorSettings = $topVendorSettingRepository->getSingleton();
        $topVendorRows = $topVendorSelector->select($topVendorSettings);
        $topVendors = array_map(function ($row) {
            $vendor = $row['seller'];
            $vendor->averageRating = $row['averageRating'];
            $vendor->salesCount = $row['salesCount'];
            $vendor->topProducts = $row['topProducts'];
            return $vendor;
        }, $topVendorRows);

        // --- Nouveauté ---
        $newItemsSectionSettings = $newItemsSectionSettingRepository->getSingleton();
        $newItemsTabs = $newItemsTabRepository->findAllOrdered();
        $newItemsByTab = [];
        foreach ($newItemsTabs as $tab) {
            $newItemsByTab[$tab->getId()] = $newItemsTabSelector->select($tab);
        }

        // --- Prochainement ---
        $comingSoonSectionSettings = $comingSoonSectionSettingRepository->getSingleton();
        $comingSoonTabs = $comingSoonTabRepository->findAllOrdered();
        $comingSoonProductsByTab = [];
        $comingSoonAllProducts = [];
        foreach ($comingSoonTabs as $tab) {
            $products = array_map(fn ($item) => $item->getProduct(), $tab->getProducts()->toArray());
            $products = array_values(array_filter($products, fn ($p) => 'futur' === $p->getStatus()));
            $comingSoonProductsByTab[$tab->getId()] = $products;
            $comingSoonAllProducts = array_merge($comingSoonAllProducts, $products);
        }
        $comingSoonAllProducts = array_values(array_unique($comingSoonAllProducts, SORT_REGULAR));
        $comingSoonBanner = $adZonePicker->pick('futur_section_banner', 'public');

        // --- Particulier ---
        $individualSectionSettings = $individualSectionSettingRepository->getSingleton();
        $individualCategories = $individualSectionCategoryRepository->findAllOrdered();
        $individualProductsByCategory = [];
        $individualAllProducts = [];
        foreach ($individualCategories as $sc) {
            $products = $individualSectionSelector->select($sc);
            $individualProductsByCategory[$sc->getId()] = $products;
            $individualAllProducts = array_merge($individualAllProducts, $products);
        }
        $individualAllProducts = array_values(array_unique($individualAllProducts, SORT_REGULAR));
        $sponsorBrands = $sponsorSectionSettingRepository->getSingleton()->isEnabled() ? $sponsorBrandRepository->findActiveOrdered() : [];
        $partners = $partnerSectionSettingRepository->getSingleton()->isEnabled() ? $partnerRepository->findActiveOrdered() : [];

        // --- Popular Tags ---
        $featuredBrands = $brandRepository->findFeaturedHomepage();
        $adLifestyleLeft = $adZonePicker->pick('homepage_lifestyle_left', 'public');
        $adLifestyleCenter = $adZonePicker->pick('homepage_lifestyle_center', 'public');
        $adLifestyleRight = $adZonePicker->pick('homepage_lifestyle_right', 'public');

        // --- Most Viewed ---
        $mostViewedSettings = $mostViewedSettingRepository->getSingleton();
        $mostViewedProducts = $productViewLogRepository->findMostVisited(7, $mostViewedSettings->getDisplayCount(), [
            'kbz' => $mostViewedSettings->isIncludeKbz(),
            'store' => $mostViewedSettings->isIncludeStore(),
            'pro' => $mostViewedSettings->isIncludePro(),
            'individual' => $mostViewedSettings->isIncludeIndividual(),
        ]);

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
            'topVendorEnabled' => $topVendorSettings->isEnabled(),
            'newItemsEnabled' => $newItemsSectionSettings->isEnabled(),
            'newItemsTabs' => $newItemsTabs,
            'newItemsByTab' => $newItemsByTab,
            'comingSoonEnabled' => $comingSoonSectionSettings->isEnabled(),
            'comingSoonTitle' => $comingSoonSectionSettings->getTitle(),
            'comingSoonTabs' => $comingSoonTabs,
            'comingSoonProductsByTab' => $comingSoonProductsByTab,
            'comingSoonAllProducts' => $comingSoonAllProducts,
            'comingSoonBanner' => $comingSoonBanner,
            'individualSectionEnabled' => $individualSectionSettings->isEnabled(),
            'individualCategories' => $individualCategories,
            'individualProductsByCategory' => $individualProductsByCategory,
            'individualAllProducts' => $individualAllProducts,
            'sponsorBrands' => $sponsorBrands,
            'partners' => $partners,
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
            'mostViewedEnabled' => $mostViewedSettings->isEnabled(),
            'footerColumns' => $footerColumns,
            'footerSocialAd' => $footerSocialAd,
            'footerBrands' => $footerBrands,
            'footerMosaicAds' => $footerMosaicAds,
            'footerCallUsPhoto' => $footerCallUsPhoto,
            'footerBottomLinks' => $footerBottomLinks,
        ]);
    }
}