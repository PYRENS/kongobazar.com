<?php

namespace App\Controller\Public;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OffersController extends AbstractController
{
    // TODO: rendre configurable via le Back-Office (table de réglages) une fois construit
    private const INITIAL_BATCH_SIZE = 8;

    #[Route('/offres/prochainement', name: 'offers_coming_soon', host: 'kongobazar.com')]
    public function comingSoon(Request $request, ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        return $this->renderOffers($request, $productRepository, $categoryRepository, 'coming_soon', 'Prochainement en rayon');
    }

    #[Route('/offres/soldes', name: 'offers_on_sale', host: 'kongobazar.com')]
    public function onSale(Request $request, ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        return $this->renderOffers($request, $productRepository, $categoryRepository, 'on_sale', 'Soldes');
    }

    #[Route('/offres/discount', name: 'offers_discount', host: 'kongobazar.com')]
    public function discount(Request $request, ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        return $this->renderOffers($request, $productRepository, $categoryRepository, 'discount', 'Offres Discount');
    }

    #[Route('/offres/particuliers-catalogue', name: 'offers_individual_catalog', host: 'kongobazar.com')]
    public function individual(Request $request, ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        return $this->renderOffers($request, $productRepository, $categoryRepository, 'individual', 'Articles des Particuliers');
    }

    #[Route('/offres/charger-plus', name: 'offers_load_more', host: 'kongobazar.com')]
    public function loadMore(Request $request, ProductRepository $productRepository): JsonResponse
    {
        $mode = $request->query->get('mode', '');
        $categoryId = $request->query->get('category') ? (int) $request->query->get('category') : null;
        $sort = $request->query->get('sort', 'newest');
        $offset = (int) $request->query->get('offset', 0);

        $products = $productRepository->findByMode($mode, $categoryId, $sort, self::INITIAL_BATCH_SIZE, $offset);
        $total = $productRepository->countByMode($mode, $categoryId);
        $newOffset = $offset + count($products);

        $html = $this->renderView('public/_partials/_offers_product_cards.html.twig', [
            'products' => $products,
            'mode' => $mode,
        ]);

        return new JsonResponse([
            'html' => $html,
            'hasMore' => $newOffset < $total,
            'nextOffset' => $newOffset,
        ]);
    }

    private function renderOffers(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        string $mode,
        string $title,
    ): Response {
        $categoryId = $request->query->get('category') ? (int) $request->query->get('category') : null;
        $sort = $request->query->get('sort', 'newest');

        $products = $productRepository->findByMode($mode, $categoryId, $sort, self::INITIAL_BATCH_SIZE, 0);
        $total = $productRepository->countByMode($mode, $categoryId);
        $categories = $categoryRepository->findRootCategories();

        return $this->render('public/offers_list.html.twig', [
            'title' => $title,
            'mode' => $mode,
            'products' => $products,
            'categories' => $categories,
            'currentCategoryId' => $categoryId,
            'currentSort' => $sort,
            'hasMore' => count($products) < $total,
            'nextOffset' => count($products),
            'breadcrumbs' => [
                ['label' => $title, 'url' => null],
            ],
        ]);
    }
}