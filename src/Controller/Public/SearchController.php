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
        'marque' => 'Marque',
        'lieu' => 'Lieu de livraison',
    ];

    #[Route('/recherche/suggest', name: 'catalog_search_suggest', host: 'kongobazar.com')]
    public function suggest(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        SellerProfileRepository $sellerProfileRepository,
        StorageInterface $storage,
        \App\Repository\BrandRepository $brandRepository,
        \App\Repository\AdministrativeUnitRepository $administrativeUnitRepository,
        \App\Service\PriceFormatter $priceFormatter,
    ): JsonResponse {
        $term = trim((string) $request->query->get('q', ''));

        if (mb_strlen($term) < 2) {
            return new JsonResponse(['products' => [], 'categories' => [], 'sellers' => [], 'relays' => [], 'brands' => [], 'locations' => []]);
        }

        // On demande 1 de plus que la limite d'affichage : s'il revient, on sait qu'il y en a
        // davantage, sans avoir besoin d'une requête de comptage séparée.
        $fetchLimit = self::PER_SECTION + 1;

        $products = $productRepository->searchByTerm($term, $fetchLimit);
        $categories = $categoryRepository->searchByTerm($term, $fetchLimit);
        $sellers = $sellerProfileRepository->searchSellersByTerm($term, $fetchLimit);
        $relays = $sellerProfileRepository->searchRelaysByTerm($term, $fetchLimit);
        $brands = $brandRepository->searchByName($term, $fetchLimit);
        $locations = $administrativeUnitRepository->searchByName($term, $fetchLimit);

        return new JsonResponse([
            'products' => $this->formatProducts(array_slice($products, 0, self::PER_SECTION), $storage, $priceFormatter),
            'productsHasMore' => count($products) > self::PER_SECTION,
            'categories' => $this->formatCategories(array_slice($categories, 0, self::PER_SECTION)),
            'categoriesHasMore' => count($categories) > self::PER_SECTION,
            'sellers' => $this->formatSellers(array_slice($sellers, 0, self::PER_SECTION)),
            'sellersHasMore' => count($sellers) > self::PER_SECTION,
            'relays' => $this->formatSellers(array_slice($relays, 0, self::PER_SECTION)),
            'relaysHasMore' => count($relays) > self::PER_SECTION,
            'brands' => array_map(fn ($b) => [
                'name' => $b->getName(),
                'logo' => $b->getLogoName() ? $storage->resolveUri($b, 'logoFile') : null,
            ], array_slice($brands, 0, self::PER_SECTION)),
            'brandsHasMore' => count($brands) > self::PER_SECTION,
            'locations' => array_map(fn ($l) => ['name' => $l->getName()], array_slice($locations, 0, self::PER_SECTION)),
            'locationsHasMore' => count($locations) > self::PER_SECTION,
        ]);
    }

    #[Route('/recherche', name: 'catalog_search', host: 'kongobazar.com')]
    public function search(
        Request $request,
        ProductRepository $productRepository,
        \App\Repository\AdministrativeUnitRepository $administrativeUnitRepository,
        CategoryRepository $categoryRepository,
        SellerProfileRepository $sellerProfileRepository,
        ProductReviewRepository $reviewRepository,
        StorageInterface $storage,
        \App\Repository\RecentlyViewedSettingRepository $recentlyViewedSettingRepository,
        \App\Repository\BrandRepository $brandRepository,
        \App\Service\AdZonePicker $adZonePicker,
        \App\Repository\SidebarFillerBannerRepository $sidebarFillerBannerRepository,
        \App\Service\NewItemsTabSelector $newItemsTabSelector,
        \App\Repository\NewItemsTabRepository $newItemsTabRepository,
        \App\Repository\NewItemsSectionSettingRepository $newItemsSectionSettingRepository,
    ): Response {
        $term = trim((string) $request->query->get('q', ''));
        $tab = $request->query->get('type', 'products');
        if (!array_key_exists($tab, self::TABS)) {
            $tab = 'products';
        }

        $hasTerm = mb_strlen($term) >= 2;

        // Filtres de l'onglet Produits (Catégorie / Marque / Prix), lus dans l'URL — Catégorie
        // et Marque acceptent plusieurs sélections à la fois (category[]=1&category[]=2...).
        $categoryIds = array_map('intval', $request->query->all('category'));
        $brandIds = array_map('intval', $request->query->all('brand'));
        $minPrice = $request->query->get('min_price') !== null && $request->query->get('min_price') !== '' ? (float) $request->query->get('min_price') : null;
        $maxPrice = $request->query->get('max_price') !== null && $request->query->get('max_price') !== '' ? (float) $request->query->get('max_price') : null;
        $locationId = $request->query->get('location') ? (int) $request->query->get('location') : null;
        $sellerTypes = $request->query->all('vendeur');
        $productConditions = $request->query->all('etat');
        $sort = (string) $request->query->get('tri', '');
        if (!array_key_exists($sort, ProductRepository::SEARCH_SORTS)) {
            $sort = '';
        }

        // Filtres actifs — réutilisés par la pagination (avant, changer de page perdait le type de
        // vendeur, l'état et le lieu) et par l'URL renvoyée au filtre en direct.
        $filterParams = array_filter([
            'q' => $term,
            'type' => $tab,
            'category' => $categoryIds ?: null,
            'brand' => $brandIds ?: null,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'vendeur' => $sellerTypes ?: null,
            'etat' => $productConditions ?: null,
            'location' => $locationId,
            'tri' => $sort ?: null,
        ], fn ($value) => null !== $value);

        // Pagination de l'onglet Produits : 30 par page, pages distinctes (pas un total qui
        // s'accumule comme "Voir plus").
        $perPage = 30;
        $currentPage = max(1, (int) $request->query->get('page', 1));
        $productsShow = $perPage; // conservé pour les autres onglets (Marque/Lieu), inchangés

        $searchFilters = $hasTerm
            ? $productRepository->getSearchFilterOptions($term, $categoryIds, $brandIds, $minPrice, $maxPrice, $locationId, $sellerTypes, $productConditions)
            : ['categories' => [], 'brands' => [], 'minPrice' => 0, 'maxPrice' => 0];

        $products = $hasTerm
            ? ('products' === $tab
                ? $productRepository->searchByTerm($term, $perPage, ($currentPage - 1) * $perPage, $categoryIds, $brandIds, $minPrice, $maxPrice, $locationId, $sellerTypes, $productConditions, $sort)
                : $productRepository->searchByTerm($term, self::PER_SECTION + 1))
            : [];
        $productsTotalCount = $hasTerm
            ? $productRepository->countSearchProducts($term, $categoryIds, $brandIds, $minPrice, $maxPrice, $locationId, $sellerTypes, $productConditions)
            : 0;
        $categories = $hasTerm ? $categoryRepository->searchByTerm($term, 'categories' === $tab ? 60 : self::PER_SECTION + 1) : [];
        $sellers = $hasTerm ? $sellerProfileRepository->searchSellersByTerm($term, 'sellers' === $tab ? 60 : self::PER_SECTION + 1) : [];
        $relays = $hasTerm ? $sellerProfileRepository->searchRelaysByTerm($term, 'relays' === $tab ? 60 : self::PER_SECTION + 1) : [];

        $categoriesTotalCount = $hasTerm ? $categoryRepository->countSearchByTerm($term) : 0;
        $sellersTotalCount = $hasTerm ? $sellerProfileRepository->countSearchSellersByTerm($term) : 0;
        $relaysTotalCount = $hasTerm ? $sellerProfileRepository->countSearchRelaysByTerm($term) : 0;

        // Onglet "Marque" : tous les produits des marques dont le NOM correspond au terme tapé
        // (pas le titre du produit) — même principe que l'onglet Catégorie, qui montre les
        // produits de la catégorie trouvée plutôt qu'une simple liste de noms.
        $marqueProducts = [];
        $marqueTotalCount = 0;
        $marqueCount = $hasTerm ? $productRepository->countByBrandNameMatch($term) : 0;
        if ($hasTerm && 'marque' === $tab) {
            $marqueProducts = $productRepository->findByBrandNameMatch($term, $productsShow);
            $marqueTotalCount = $marqueCount;
        }

        // Onglet "Lieu de livraison" : tous les produits livrables vers un lieu dont le nom
        // correspond au terme tapé (ou tout ce que ce lieu contient).
        $lieuProducts = [];
        $lieuTotalCount = 0;
        $lieuCount = 0;
        if ($hasTerm) {
            $matchedLocations = $administrativeUnitRepository->searchByName($term);
            $matchedLocationIds = array_map(fn ($l) => $l->getId(), $matchedLocations);
            $expandedLocationIds = $matchedLocationIds ? $administrativeUnitRepository->getDescendantIdsForMany($matchedLocationIds) : [];

            if ($expandedLocationIds) {
                $lieuCount = $productRepository->countByLocationNameMatch($expandedLocationIds);
                if ('lieu' === $tab) {
                    $lieuProducts = $productRepository->findByLocationNameMatch($expandedLocationIds, $productsShow);
                    $lieuTotalCount = $lieuCount;
                }
            }
        }

        $reviewStats = in_array($tab, ['products', 'marque', 'lieu'], true)
            ? $reviewRepository->getStatsForProducts(array_map(fn ($p) => $p->getId(), match ($tab) {
                'marque' => $marqueProducts,
                'lieu' => $lieuProducts,
                default => $products,
            }))
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

        $newItemsSectionSettings = $newItemsSectionSettingRepository->getSingleton();
        $newItemsTabs = $newItemsTabRepository->findAllOrdered();
        $newItemsByTab = [];
        $newItemsTabProductCounts = [];
        foreach ($newItemsTabs as $newItemsTab) {
            $newItemsByTab[$newItemsTab->getId()] = $newItemsTabSelector->select($newItemsTab);
            $newItemsTabProductCounts[$newItemsTab->getId()] = $newItemsTab->getProductCount();
        }

        $searchBanners = array_filter([
            $adZonePicker->pick('search_results_banner_1', 'public'),
            $adZonePicker->pick('search_results_banner_2', 'public'),
            $adZonePicker->pick('search_results_banner_3', 'public'),
        ]);

        // "Autour de moi" : uniquement si connecté avec un lieu renseigné dans le profil.
        // Pagination réelle, 30 par page (même principe que l'onglet Produits).
        $currentUser = $this->getUser();
        $aroundMePerPage = 30;
        $aroundMePage = max(1, (int) $request->query->get('around_page', 1));
        $aroundMeProducts = [];
        $aroundMeTotalCount = 0;
        if ($currentUser instanceof \App\Entity\User && $currentUser->getAdministrativeUnit()) {
            $aroundMeZoneIds = $productRepository->resolveAroundLocationZoneIds($currentUser->getAdministrativeUnit(), $administrativeUnitRepository, $aroundMePerPage);
            $aroundMeTotalCount = $productRepository->countByDeliveryZoneIds($aroundMeZoneIds);
            $aroundMeProducts = $productRepository->findByDeliveryZoneIdsPaginated($aroundMeZoneIds, $aroundMePerPage, ($aroundMePage - 1) * $aroundMePerPage);
        }

        $templateParams = [
            'searchBanners' => $searchBanners,
            'aroundMeProducts' => $aroundMeProducts,
            'aroundMeCurrentPage' => $aroundMePage,
            'aroundMeTotalPages' => (int) max(1, ceil($aroundMeTotalCount / $aroundMePerPage)),
            'isLoggedIn' => null !== $currentUser,
            'sidebarFillerBanners' => $sidebarFillerBannerRepository->findActiveRandomOrder(),
            'newItemsEnabled' => $newItemsSectionSettings->isEnabled(),
            'newItemsTabs' => $newItemsTabs,
            'newItemsByTab' => $newItemsByTab,
            'newItemsTabProductCounts' => $newItemsTabProductCounts,
            'term' => $term,
            'filterParams' => $filterParams,
            'sortOptions' => ProductRepository::SEARCH_SORTS,
            'currentSort' => $sort,
            // Repères chiffrés = la sélection réellement affichée (mêmes chiffres que les filtres), mis à jour en direct.
            'listingHighlights' => $hasTerm ? $searchFilters : null,
            'perPage' => $perPage,
            'tab' => $tab,
            'tabs' => self::TABS,
            'products' => $products,
            'categories' => $categories,
            'sellers' => $sellers,
            'relays' => $relays,
            'reviewStats' => $reviewStats,
            'recentlyViewedProducts' => $recentlyViewedProducts,
            'searchFilters' => $searchFilters,
            'selectedCategoryIds' => $categoryIds,
            'selectedBrandIds' => $brandIds,
            'selectedMinPrice' => $minPrice,
            'selectedMaxPrice' => $maxPrice,
            'locations' => $administrativeUnitRepository->findActiveRootUnits(),
            'currentLocationId' => $locationId,
            'selectedSellerTypes' => $sellerTypes,
            'selectedConditions' => $productConditions,
            'marqueProducts' => $marqueProducts,
            'marqueCount' => $marqueCount,
            'lieuProducts' => $lieuProducts,
            'lieuCount' => $lieuCount,
            'productsTotalCount' => $productsTotalCount,
            'categoriesTotalCount' => $categoriesTotalCount,
            'sellersTotalCount' => $sellersTotalCount,
            'relaysTotalCount' => $relaysTotalCount,
            'productsHasMore' => 'products' === $tab && $productsShow < $productsTotalCount,
            'marqueHasMore' => 'marque' === $tab && $productsShow < $marqueTotalCount,
            'lieuHasMore' => 'lieu' === $tab && $productsShow < $lieuTotalCount,
            'productsNextShow' => $productsShow + 30,
            'currentPage' => $currentPage,
            'totalPages' => (int) max(1, ceil($productsTotalCount / $perPage)),
        ];

        // Appelé par le script de filtre en direct (fetch avec l'en-tête X-Requested-With) :
        // on ne renvoie que le morceau HTML concerné, pas toute la page. Ce même principe
        // (un indicateur "requête en direct" sur la même route) pourra être repris tel quel
        // sur d'autres pages au fur et à mesure qu'on les enrichit.
        if ($request->isXmlHttpRequest() && 'products' === $tab) {
            return $this->json([
                'html' => $this->renderView('public/_partials/_search_products_results.html.twig', $templateParams),
                'filtersHtml' => $this->renderView('public/_partials/_search_filters_body.html.twig', $templateParams),
                'highlightsHtml' => $this->renderView('public/_partials/_listing_highlights.html.twig', ['highlights' => $searchFilters]),
                'pageInfoHtml' => $this->renderView('public/_partials/_listing_page_info.html.twig', [
                    'total' => $productsTotalCount, 'currentPage' => $currentPage, 'totalPages' => (int) max(1, ceil($productsTotalCount / $perPage)), 'perPage' => $perPage,
                ]),
                'counts' => [
                    'products' => $productsTotalCount,
                ],
                'url' => $this->generateUrl('catalog_search', $filterParams),
            ]);
        }

        return $this->render('public/search_results.html.twig', $templateParams + [
            'breadcrumbs' => $hasTerm ? [
                ['label' => 'Recherche', 'url' => $this->generateUrl('catalog_search', ['q' => $term])],
                ['label' => '"' . $term . '"', 'url' => null],
            ] : [
                ['label' => 'Recherche', 'url' => null],
            ],
        ]);
    }

    private function formatProducts(array $products, StorageInterface $storage, \App\Service\PriceFormatter $priceFormatter): array
    {
        return array_map(function ($product) use ($storage, $priceFormatter) {
            $firstImage = $product->getImages()->first() ?: null;
            $imageUrl = $firstImage ? $storage->resolveUri($firstImage, 'imageFile') : null;

            return [
                'title' => $product->getTitle(),
                'url' => $this->generateUrl('catalog_product', ['slug' => $product->getSlug()]),
                // Montants déjà convertis et formatés dans la devise choisie par le visiteur (USD/CDF)
                'price' => $priceFormatter->display($product->getDisplayCurrentPrice(), $product->getCurrency()),
                'oldPrice' => $product->getDisplayOldPrice() ? $priceFormatter->display($product->getDisplayOldPrice(), $product->getCurrency()) : null,
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

    #[Route('/recherche/autour-de-moi', name: 'catalog_around_me_by_coords', host: 'kongobazar.com', methods: ['GET'])]
    public function aroundMeByCoords(
        Request $request,
        ProductRepository $productRepository,
        \App\Repository\AdministrativeUnitRepository $administrativeUnitRepository,
    ): JsonResponse {
        $lat = $request->query->get('lat');
        $lon = $request->query->get('lon');
        if (null === $lat || null === $lon) {
            return new JsonResponse(['html' => ''], 400);
        }

        $results = $productRepository->findAroundCoordinates((float) $lat, (float) $lon, $administrativeUnitRepository, 15);

        return new JsonResponse([
            'html' => $this->renderView('public/_partials/_around_me_grid.html.twig', ['aroundMeProducts' => $results]),
        ]);
    }
    #[Route('/geo/plus-proche', name: 'geo_nearest_unit', host: 'kongobazar.com', methods: ['GET'])]
    public function nearestUnit(Request $request, \App\Repository\AdministrativeUnitRepository $administrativeUnitRepository): JsonResponse
    {
        $lat = $request->query->get('lat');
        $lon = $request->query->get('lon');
        if (null === $lat || null === $lon) {
            return new JsonResponse(['found' => false], 400);
        }

        $unit = $administrativeUnitRepository->findNearestUnit((float) $lat, (float) $lon);
        if (!$unit) {
            return new JsonResponse(['found' => false]);
        }

        return new JsonResponse([
            'found' => true,
            'id' => $unit->getId(),
            'name' => $unit->getName(),
        ]);
    }
}