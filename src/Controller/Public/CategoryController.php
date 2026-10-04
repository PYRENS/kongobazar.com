<?php

namespace App\Controller\Public;

use App\Repository\AdministrativeUnitRepository;
use App\Repository\BrandRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\ProductReviewRepository;
use App\Repository\SidebarFillerBannerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Vich\UploaderBundle\Storage\StorageInterface;

class CategoryController extends AbstractController
{
    #[Route('/categorie/{slug}', name: 'catalog_category', host: 'kongobazar.com')]
    public function show(
        string $slug,
        Request $request,
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        BrandRepository $brandRepository,
        AdministrativeUnitRepository $administrativeUnitRepository,
        SidebarFillerBannerRepository $sidebarFillerBannerRepository,
        ProductReviewRepository $reviewRepository,
        StorageInterface $storage,
        \App\Service\AdZonePicker $adZonePicker,
    ): Response {
        $category = $categoryRepository->findOneBy(['slug' => $slug]);
        if (!$category) {
            throw $this->createNotFoundException('Catégorie introuvable.');
        }

        $directChildren = $category->getChildren();

        // Si une ou plusieurs sous-catégories sont cochées, on restreint la recherche à
        // elles (+ leurs propres descendantes) ; sinon, tout le rayon (catégorie + tout
        // ce qu'elle contient).
        $selectedCategoryIds = array_map('intval', $request->query->all('category'));
        if ($selectedCategoryIds) {
            $categoryIds = [];
            foreach ($directChildren as $child) {
                if (in_array($child->getId(), $selectedCategoryIds, true)) {
                    foreach ($child->getDescendantCategories() as $c) {
                        $categoryIds[] = $c->getId();
                    }
                }
            }
        } else {
            $categoryIds = array_map(fn ($c) => $c->getId(), $category->getDescendantCategories());
        }

        $brandIds = array_map('intval', $request->query->all('brand'));
        $minPrice = $request->query->get('min_price') !== null && $request->query->get('min_price') !== '' ? (float) $request->query->get('min_price') : null;
        $maxPrice = $request->query->get('max_price') !== null && $request->query->get('max_price') !== '' ? (float) $request->query->get('max_price') : null;
        $locationId = $request->query->get('location') ? (int) $request->query->get('location') : null;
        $sellerTypes = $request->query->all('vendeur');
        $productConditions = $request->query->all('etat');
        $sort = (string) $request->query->get('tri', '');
        if (!array_key_exists($sort, \App\Repository\ProductRepository::SEARCH_SORTS)) {
            $sort = '';
        }

        $perPage = 30;
        $page = max(1, (int) $request->query->get('page', 1));

        $searchFilters = $productRepository->getSearchFilterOptions('', $categoryIds, $brandIds, $minPrice, $maxPrice, $locationId, $sellerTypes, $productConditions);
        $totalCount = $productRepository->countSearchProducts('', $categoryIds, $brandIds, $minPrice, $maxPrice, $locationId, $sellerTypes, $productConditions);
        $products = $productRepository->searchByTerm('', $perPage, ($page - 1) * $perPage, $categoryIds, $brandIds, $minPrice, $maxPrice, $locationId, $sellerTypes, $productConditions, $sort);

        $reviewStats = $reviewRepository->getStatsForProducts(array_map(fn ($p) => $p->getId(), $products));

        // Compteur par sous-catégorie directe, pour la case à cocher (compte sa propre
        // branche entière, pas seulement les produits qui lui sont rattachés exactement).
        $subcategories = [];
        foreach ($directChildren as $child) {
            $childIds = array_map(fn ($c) => $c->getId(), $child->getDescendantCategories());
            $count = $categoryRepository->countProductsIn($childIds);
            if ($count > 0) {
                $subcategories[] = ['id' => $child->getId(), 'name' => $child->getName(), 'count' => $count];
            }
        }

        // "Autour de moi" : même logique qu'en recherche — connecté avec un lieu renseigné.
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

        $breadcrumbs = [];
        foreach ($category->getAncestors() as $ancestor) {
            // getAncestors() inclut la catégorie courante en dernier : elle est ajoutée juste après, sans lien.
            if ($ancestor === $category) {
                continue;
            }
            $breadcrumbs[] = [
                'label' => $ancestor->getName(),
                'url' => $this->generateUrl('catalog_category', ['slug' => $ancestor->getSlug()]),
            ];
        }
        $breadcrumbs[] = ['label' => $category->getName(), 'url' => null];

        // Filtres actifs — réutilisés par la pagination (avant, changer de page perdait la
        // sous-catégorie, le type de vendeur, l'état et le lieu) et par l'URL du filtre en direct.
        $filterParams = array_filter([
            'slug' => $category->getSlug(),
            'category' => $selectedCategoryIds ?: null,
            'brand' => $brandIds ?: null,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'vendeur' => $sellerTypes ?: null,
            'etat' => $productConditions ?: null,
            'location' => $locationId,
            'tri' => $sort ?: null,
        ], fn ($value) => null !== $value);

        $templateParams = [
            'category' => $category,
            'articleCount' => $totalCount,
            'children' => $directChildren,
            'subcategories' => $subcategories,
            'products' => $products,
            'reviewStats' => $reviewStats,
            'searchFilters' => $searchFilters,
            'selectedCategoryIds' => $selectedCategoryIds,
            'selectedBrandIds' => $brandIds,
            'selectedMinPrice' => $minPrice,
            'selectedMaxPrice' => $maxPrice,
            'locations' => $administrativeUnitRepository->findActiveRootUnits(),
            'currentLocationId' => $locationId,
            'selectedSellerTypes' => $sellerTypes,
            'selectedConditions' => $productConditions,
            'currentPage' => $page,
            'totalPages' => (int) max(1, ceil($totalCount / $perPage)),
            'productsHasMore' => false,
            'perPage' => $perPage,
            'filterParams' => $filterParams,
            'sortOptions' => \App\Repository\ProductRepository::SEARCH_SORTS,
            'currentSort' => $sort,
            // Repères chiffrés = la sélection réellement affichée (mêmes chiffres que les filtres), mis à jour en direct.
            'listingHighlights' => $searchFilters,
            // Carte sponsorisée intercalée dans la grille (aussi lors des mises à jour en direct).
            'categoryGridCardAd' => count($products) >= 4 ? $adZonePicker->pickForCategory('category_grid_card', $category) : null,
        ];

        // Appelé par le filtre en direct : ne renvoyer que les morceaux HTML concernés,
        // pas toute la page — c'était l'absence de cette branche qui empêchait tout
        // filtrage de fonctionner sur cette page.
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'html' => $this->renderView('public/_partials/_category_products_results.html.twig', $templateParams),
                'filtersHtml' => $this->renderView('public/_partials/_search_filters_body.html.twig', $templateParams + ['hideCategoryFilter' => true]),
                'highlightsHtml' => $this->renderView('public/_partials/_listing_highlights.html.twig', ['highlights' => $searchFilters]),
                'pageInfoHtml' => $this->renderView('public/_partials/_listing_page_info.html.twig', [
                    'total' => $totalCount, 'currentPage' => $page, 'totalPages' => (int) max(1, ceil($totalCount / $perPage)), 'perPage' => $perPage,
                ]),
                'counts' => ['products' => $totalCount],
                'url' => $this->generateUrl('catalog_category', $filterParams),
            ]);
        }

        // Bannières haut et bas de liste : uniquement au chargement complet (pas à chaque filtre),
        // pour ne pas gonfler les compteurs d'affichage. Les 3 du bas ne montrent jamais la même pub.
        $categoryBottomAds = [];
        foreach ([1, 2, 3] as $n) {
            $zoneKey = 'category_bottom_banner_' . $n;
            $ad = $adZonePicker->pickForCategory($zoneKey, $category, 'public', array_map(fn ($row) => $row['ad']->getId(), $categoryBottomAds));
            if ($ad) {
                $categoryBottomAds[] = ['ad' => $ad, 'zoneKey' => $zoneKey];
            }
        }

        return $this->render('public/category_show.html.twig', $templateParams + [
            'categoryTopAd' => $adZonePicker->pickForCategory('category_top_banner', $category),
            'categoryBottomAds' => $categoryBottomAds,
            'sidebarFillerBanners' => $sidebarFillerBannerRepository->findForCategory($category),
            'breadcrumbs' => $breadcrumbs,
            'aroundMeProducts' => $aroundMeProducts,
            'aroundMeCurrentPage' => $aroundMePage,
            'aroundMeTotalPages' => (int) max(1, ceil($aroundMeTotalCount / $aroundMePerPage)),
            'isLoggedIn' => null !== $currentUser,
        ]);
    }
}