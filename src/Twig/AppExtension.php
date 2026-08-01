<?php

namespace App\Twig;

use App\Repository\AdvertisementRepository;
use App\Repository\CartRepository;
use App\Repository\CategoryRepository;
use App\Repository\CategoryViewLogRepository;
use App\Repository\CustomMenuItemRepository;
use App\Repository\ProductRepository;
use App\Service\CartService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly CategoryRepository $categoryRepository,
        private readonly CategoryViewLogRepository $categoryViewLogRepository,
        private readonly CustomMenuItemRepository $customMenuItemRepository,
        private readonly AdvertisementRepository $advertisementRepository,
        private readonly CartRepository $cartRepository,
        private readonly Security $security,
        private readonly CartService $cartService,
        private readonly ProductRepository $productRepository,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('current_currency', [$this, 'getCurrentCurrency']),
            new TwigFunction('current_locale', [$this, 'getCurrentLocale']),
            new TwigFunction('root_categories', [$this, 'getRootCategories']),
            new TwigFunction('trending_categories', [$this, 'getTrendingCategories']),
            new TwigFunction('header_custom_menu', [$this, 'getHeaderCustomMenu']),
            new TwigFunction('mega_menu_ads', [$this, 'getMegaMenuAds']),
            new TwigFunction('current_cart', [$this, 'getCurrentCart']),
            new TwigFunction('cart_summary', [$this, 'getCartSummary']),
            new TwigFunction('cart_display_subtotal', [$this, 'getCartDisplaySubtotal']),
            new TwigFunction('best_sellers_widget', [$this, 'getBestSellersWidget']),
            new TwigFunction('product_sidebar_ad', [$this, 'getProductSidebarAd']),
            new TwigFunction('top_rayons', [$this, 'getTopRayons']),
        ];
    }

    public function getCurrentCurrency(): string
    {
        return $this->requestStack->getSession()->get('_currency', 'USD');
    }

    public function getCurrentLocale(): string
    {
        return $this->requestStack->getSession()->get('_locale', 'fr');
    }

    public function getRootCategories(): array
    {
        return $this->categoryRepository->findRootCategories();
    }

    public function getTrendingCategories(): array
    {
        return $this->categoryViewLogRepository->findMostVisited(7, 8);
    }

    public function getHeaderCustomMenu(): array
    {
        return $this->customMenuItemRepository->findByLocationAndSpace('header_main', 'public');
    }

    public function getMegaMenuAds(): array
    {
        return $this->advertisementRepository->findActiveByZone('mega_menu_catalogue', 'public');
    }

    public function getCartSummary(): array
    {
        return $this->cartService->getSummary($this->cartService->getCurrentCart());
    }

    public function getCartDisplaySubtotal(): array
    {
        $summary = $this->getCartSummary();
        return $this->cartService->getDisplaySubtotal($summary);
    }

    public function getCurrentCart(): ?object
    {
        $user = $this->security->getUser();
        if (!$user) {
            return null; // Panier invité : à brancher avec CartService plus tard
        }

        return $this->cartRepository->findOneBy(['user' => $user]);
    }

    public function getBestSellersWidget(int $limit = 4): array
    {
        return $this->productRepository->findBestSellersInStock($limit);
    }

    public function getProductSidebarAd(): ?object
    {
        return $this->advertisementRepository->findOneActiveByZone('product_sidebar', 'public');
    }

    public function getTopRayons(): array
    {
        return $this->categoryRepository->findTopRayons();
    }
}