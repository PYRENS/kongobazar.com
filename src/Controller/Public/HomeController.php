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
        BrandRepository $brandRepository,
        BlogPostRepository $blogPostRepository,
        CustomMenuItemRepository $customMenuItemRepository,
        SellerProfileRepository $sellerProfileRepository,
        ReviewRepository $reviewRepository,
        CategoryViewLogRepository $categoryViewLogRepository,
        ProductViewLogRepository $productViewLogRepository,
        CartRepository $cartRepository,
    ): Response {
        $rootCategories = $categoryRepository->findRootCategories();

        // --- Header ---
        $user = $this->getUser();
        $cart = $user ? $cartRepository->findOneBy(['user' => $user]) : null;
        // TODO : résolution du panier invité via jeton de session, une fois CartService construit

        // --- Hero ---
        $heroSlides = $advertisementRepository->findActiveByZone('homepage_hero_main', 'public');
        $sideAdTop = $advertisementRepository->findOneActiveByZone('homepage_hero_side_top', 'public');
        $sideAdBottom = $advertisementRepository->findOneActiveByZone('homepage_hero_side_bottom', 'public');

        // --- Colonne gauche ---
        $adSidebarTop = $advertisementRepository->findOneActiveByZone('sidebar_top', 'public');
        $adSidebarMiddle = $advertisementRepository->findOneActiveByZone('sidebar_middle', 'public');
        $bestSellers = $productRepository->findBestSellersInStock(8);
        $newArrivals = $productRepository->findNewArrivals(8);
        $latestPost = $blogPostRepository->findLatestPublished();

        // --- Centre : promo + deals ---
        $promoStrip = $advertisementRepository->findOneActiveByZone('homepage_promo_strip', 'public');
        $dealsProducts = $productRepository->findActiveDeals(4);
        $centerAdBanner = $advertisementRepository->findOneActiveByZone('homepage_center_banner', 'public');

        // --- Articles tendances ---
        $trendingTabCategories = $categoryRepository->findFeaturedHomepageTabs();
        $trendingProductsByCategory = [];
        foreach ($trendingTabCategories as $category) {
            $trendingProductsByCategory[$category->getSlug()] = $productRepository->findByCategoryForTrending($category->getDescendantCategories(), 5);
        }

        // --- Blocs catégorie complets ---
        $blockColors = ['#2FA8E0', '#e91e63', '#43a047', '#f57c00'];
        $blockIcons = ['bi-cpu', 'bi-bag-heart', 'bi-house-door', 'bi-collection'];
        $featuredBlockCategories = $categoryRepository->findFeaturedHomepageBlocks();
        $featuredBlocks = [];
        foreach ($featuredBlockCategories as $i => $category) {
            $featuredBlocks[] = [
                'category' => $category,
                'color' => $blockColors[$i % count($blockColors)],
                'icon' => $blockIcons[$i % count($blockIcons)],
                'productsBySort' => [
                    'best_sellers' => $productRepository->findByCategorySort($category->getDescendantCategories(), 'best_sellers', 4),
                    'new_arrivals' => $productRepository->findByCategorySort($category->getDescendantCategories(), 'new_arrivals', 4),
                    'featured' => $productRepository->findByCategorySort($category->getDescendantCategories(), 'featured', 4),
                ],
                'banner' => $advertisementRepository->findOneActiveByCategory($category, 'public'),
            ];
        }

        // --- Top Catégories / Top Vendeur ---
        $topCategories = $categoryRepository->findTopCategoriesIllustrated(8);
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
        $adLifestyleLeft = $advertisementRepository->findOneActiveByZone('homepage_lifestyle_left', 'public');
        $adLifestyleCenter = $advertisementRepository->findOneActiveByZone('homepage_lifestyle_center', 'public');
        $adLifestyleRight = $advertisementRepository->findOneActiveByZone('homepage_lifestyle_right', 'public');

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
        $footerSocialAd = $advertisementRepository->findOneActiveByZone('footer_social_banner', 'public');
        $footerBrands = $brandRepository->findFeaturedHomepage(); // réutilisé pour le bandeau de mots-clés
        $footerMosaicAds = $advertisementRepository->findActiveByZone('footer_mosaic', 'public'); // plusieurs photos
        $footerCallUsPhoto = $advertisementRepository->findOneActiveByZone('footer_callus_photo', 'public');
        $footerBottomLinks = $customMenuItemRepository->findByLocationAndSpace('footer_bottom_links', 'public');

        // --- Mega menu (utilisé dans le header, mêmes rootCategories) ---
        $megaMenuAds = $advertisementRepository->findActiveByZone('mega_menu_catalogue', 'public');

        return $this->render('public/home.html.twig', [  
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