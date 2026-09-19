<?php

namespace App\Controller\Public;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SellerProfileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Vich\UploaderBundle\Storage\StorageInterface;

class SearchController extends AbstractController
{
    private const MAX_TOTAL_RESULTS = 10;
    private const MAX_CATEGORY_RESULTS = 5;
    private const MAX_SELLER_RESULTS = 3;

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
            return new JsonResponse(['products' => [], 'categories' => [], 'sellers' => []]);
        }

        // 1. Catégories d'abord, plafonnées à 5
        $categories = $categoryRepository->searchByTerm($term, self::MAX_CATEGORY_RESULTS);
        $categoryCount = count($categories);

        // 2. Vendeurs (boutique/pro/relay), plafonnés à 3
        $sellers = $sellerProfileRepository->searchByTerm($term, self::MAX_SELLER_RESULTS);
        $sellerCount = count($sellers);

        // 3. Les produits comblent le reste jusqu'à 10 au total
        $productLimit = self::MAX_TOTAL_RESULTS - $categoryCount - $sellerCount;
        $products = $productRepository->searchByTerm($term, max(0, $productLimit));

        $categoryResults = array_map(fn ($category) => [
            'name' => $category->getName(),
            'url' => $this->generateUrl('catalog_category', ['slug' => $category->getSlug()]),
            'icon' => $category->getIcon() ?? 'bi-tag',
        ], $categories);

        $sellerResults = array_map(fn ($seller) => [
            'name' => $seller->getDisplayName(),
            'url' => $this->generateUrl('partner_show', ['slug' => $seller->getSlug()]),
            'typeLabel' => $seller->getTypeLabel(),
            'logo' => $seller->getLogoName() ? '/media/products/' . $seller->getLogoName() : null,
        ], $sellers);

        $productResults = array_map(function ($product) use ($storage) {
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

        return new JsonResponse([
            'products' => $productResults,
            'categories' => $categoryResults,
            'sellers' => $sellerResults,
        ]);
    }

    #[Route('/recherche', name: 'catalog_search', host: 'kongobazar.com')]
    public function search(Request $request, ProductRepository $productRepository): Response
    {
        $term = trim((string) $request->query->get('q', ''));
        $products = mb_strlen($term) >= 2 ? $productRepository->searchByTerm($term) : [];

        return $this->render('public/search_results.html.twig', [
            'term' => $term,
            'products' => $products,
        ]);
    }
}