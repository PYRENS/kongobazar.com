<?php

namespace App\Controller\Public;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
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

    #[Route('/recherche/suggest', name: 'catalog_search_suggest', host: 'kongobazar.com')]
    public function suggest(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        StorageInterface $storage,
    ): JsonResponse {
        $term = trim((string) $request->query->get('q', ''));

        if (mb_strlen($term) < 2) {
            return new JsonResponse(['products' => [], 'categories' => []]);
        }

        // 1. Catégories d'abord, plafonnées à 5
        $categories = $categoryRepository->searchByTerm($term, self::MAX_CATEGORY_RESULTS);
        $categoryCount = count($categories);

        // 2. Les produits comblent le reste jusqu'à 10 au total
        $productLimit = self::MAX_TOTAL_RESULTS - $categoryCount;
        $products = $productRepository->searchByTerm($term, $productLimit);

        $categoryResults = array_map(fn ($category) => [
            'name' => $category->getName(),
            'url' => $this->generateUrl('catalog_category', ['slug' => $category->getSlug()]),
            'icon' => $category->getIcon() ?? 'bi-tag',
        ], $categories);

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