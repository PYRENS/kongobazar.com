<?php

namespace App\Controller\Public;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\ProductReviewRepository;
use App\Repository\SellerProfileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Vich\UploaderBundle\Storage\StorageInterface;

class SearchController extends AbstractController
{
    /** Nombre de résultats affichés par section dans les suggestions et par défaut dans un onglet. */
    private const PER_SECTION = 5;

    public const TABS = [
        'products' => 'Produits',
        'categories' => 'Catégories',
        'sellers' => 'Vendeurs',
        'relays' => 'Points relais',
    ];

    #[Route('/recherche/suggest', name: 'catalog_search_suggest', host: 'kongobazar.com')]
    public function suggest(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        SellerProfileRepository $sellerProfileRepository,
        StorageInterface $storage,
    ): JsonResponse {
        $term = trim((string) $request->query->get('q', ''));

        if (mb_strlen($term) < 2) {
            return new JsonResponse(['products' => [], 'categories' => [], 'sellers' => [], 'relays' => []]);
        }

        // On demande 1 de plus que la limite d'affichage : s'il revient, on sait qu'il y en a
        // davantage, sans avoir besoin d'une requête de comptage séparée.
        $fetchLimit = self::PER_SECTION + 1;

        $products = $productRepository->searchByTerm($term, $fetchLimit);
        $categories = $categoryRepository->searchByTerm($term, $fetchLimit);
        $sellers = $sellerProfileRepository->searchSellersByTerm($term, $fetchLimit);
        $relays = $sellerProfileRepository->searchRelaysByTerm($term, $fetchLimit);

        return new JsonResponse([
            'products' => $this->formatProducts(array_slice($products, 0, self::PER_SECTION), $storage),
            'productsHasMore' => count($products) > self::PER_SECTION,
            'categories' => $this->formatCategories(array_slice($categories, 0, self::PER_SECTION)),
            'categoriesHasMore' => count($categories) > self::PER_SECTION,
            'sellers' => $this->formatSellers(array_slice($sellers, 0, self::PER_SECTION)),
            'sellersHasMore' => count($sellers) > self::PER_SECTION,
            'relays' => $this->formatSellers(array_slice($relays, 0, self::PER_SECTION)),
            'relaysHasMore' => count($relays) > self::PER_SECTION,
        ]);
    }

    #[Route('/recherche', name: 'catalog_search', host: 'kongobazar.com')]
    public function search(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        SellerProfileRepository $sellerProfileRepository,
        ProductReviewRepository $reviewRepository,
        StorageInterface $storage,
        \App\Repository\RecentlyViewedSettingRepository $recentlyViewedSettingRepository,
    ): Response {
        $term = trim((string) $request->query->get('q', ''));
        $tab = $request->query->get('type', 'products');
        if (!array_key_exists($tab, self::TABS)) {
            $tab = 'products';
        }

        $hasTerm = mb_strlen($term) >= 2;

        // On affiche jusqu'à 60 résultats dans l'onglet ouvert (cohérent avec les grilles du site) ;
        // les autres onglets ne récupèrent que de quoi afficher leur compte dans leur libellé.
        $products = $hasTerm ? $productRepository->searchByTerm($term, 'products' === $tab ? 60 : self::PER_SECTION + 1) : [];
        $categories = $hasTerm ? $categoryRepository->searchByTerm($term, 'categories' === $tab ? 60 : self::PER_SECTION + 1) : [];
        $sellers = $hasTerm ? $sellerProfileRepository->searchSellersByTerm($term, 'sellers' === $tab ? 60 : self::PER_SECTION + 1) : [];
        $relays = $hasTerm ? $sellerProfileRepository->searchRelaysByTerm($term, 'relays' === $tab ? 60 : self::PER_SECTION + 1) : [];

        $reviewStats = 'products' === $tab
            ? $reviewRepository->getStatsForProducts(array_map(fn ($p) => $p->getId(), $products))
            : [];

        // Réutilise exactement le même mécanisme (cookie) que la section homonyme de l'accueil.
        $recentlyViewedSetting = $recentlyViewedSettingRepository->getSingleton();
        $recentlyViewedProducts = [];
        if ($recentlyViewedSetting->isEnabled()) {
            $recentIds = json_decode($request->cookies->get('kb_recently_viewed', '[]'), true) ?: [];
            // Pas de limite à 5 ici : on récupère tout l'historique disponible (jusqu'à 20, la
            // limite de stockage du cookie), le découpage par pages de 5 se fait à l'affichage.
            if ($recentIds) {
                $found = $productRepository->createQueryBuilder('p')
                    ->andWhere('p.id IN (:ids)')->setParameter('ids', $recentIds)
                    ->andWhere('p.status = :status')->setParameter('status', 'active')
                    ->getQuery()->getResult();
                $byId = [];
                foreach ($found as $p) { $byId[$p->getId()] = $p; }
                foreach ($recentIds as $id) {
                    if (isset($byId[$id])) { $recentlyViewedProducts[] = $byId[$id]; }
                }
            }
        }

        return $this->render('public/search_results.html.twig', [
            'term' => $term,
            'tab' => $tab,
            'tabs' => self::TABS,
            'products' => $products,
            'categories' => $categories,
            'sellers' => $sellers,
            'relays' => $relays,
            'reviewStats' => $reviewStats,
            'recentlyViewedProducts' => $recentlyViewedProducts,
            'breadcrumbs' => $hasTerm ? [
                ['label' => 'Recherche', 'url' => $this->generateUrl('catalog_search', ['q' => $term])],
                ['label' => '"' . $term . '"', 'url' => null],
            ] : [
                ['label' => 'Recherche', 'url' => null],
            ],
        ]);
    }

    private function formatProducts(array $products, StorageInterface $storage): array
    {
        return array_map(function ($product) use ($storage) {
            $firstImage = $product->getImages()->first() ?: null;
            $imageUrl = $firstImage ? $storage->resolveUri($firstImage, 'imageFile') : null;

            return [
                'title' => $product->getTitle(),
                'url' => $this->generateUrl('catalog_product', ['slug' => $product->getSlug()]),
                'price' => $product->getBasePrice(),
                'currency' => $product->getCurrency(),
                'image' => $imageUrl,
            ];
        }, $products);
    }

    private function formatCategories(array $categories): array
    {
        return array_map(fn ($category) => [
            'name' => $category->getName(),
            'url' => $this->generateUrl('catalog_category', ['slug' => $category->getSlug()]),
            'icon' => $category->getIcon() ?? 'bi-tag',
        ], $categories);
    }

    private function formatSellers(array $sellers): array
    {
        return array_map(fn ($seller) => [
            'name' => $seller->getDisplayName(),
            'url' => $this->generateUrl('partner_show', ['slug' => $seller->getSlug()]),
            'typeLabel' => $seller->getTypeLabel(),
            'logo' => $seller->getLogoName() ? '/media/products/' . $seller->getLogoName() : null,
        ], $sellers);
    }
}