<?php

namespace App\Controller\Public;

use App\Entity\HomeCategoryBlockSortTab;
use App\Repository\CategoryRepository;
use App\Repository\HomeCategoryBlockSettingRepository;
use App\Repository\HomeCategoryBlockSortTabRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeCategoryBlockAjaxController extends AbstractController
{
    /** Rafraîchit la grille produits d'un bloc catégorie de l'accueil, sans recharger la page (changement d'onglet tri OU de sous-catégorie). */
    #[Route('/accueil/bloc-categorie/{blockId}/produits', name: 'public_home_category_block_products', host: 'kongobazar.com', methods: ['GET'], requirements: ['blockId' => '\d+'])]
    public function products(int $blockId, Request $request, HomeCategoryBlockSettingRepository $blockRepository, HomeCategoryBlockSortTabRepository $sortTabRepository, CategoryRepository $categoryRepository, ProductRepository $productRepository): Response
    {
        $block = $blockRepository->find($blockId);
        if (!$block) {
            throw $this->createNotFoundException();
        }

        $sort = $request->query->get('sort', 'best_sellers');
        if (!array_key_exists($sort, HomeCategoryBlockSortTab::SORT_KEYS)) {
            $sort = 'best_sellers';
        }

        // Le nombre de produits est réglé par bloc et par onglet — 4 par défaut si jamais l'onglet n'existe pas encore.
        $tab = $sortTabRepository->findOneByBlockAndSortKey($block, $sort);
        $count = $tab ? $tab->getProductCount() : 4;

        $subcategoryId = $request->query->get('subcategory') ? (int) $request->query->get('subcategory') : null;
        $scopeCategory = $subcategoryId ? $categoryRepository->find($subcategoryId) : $block->getCategory();
        if (!$scopeCategory) {
            $scopeCategory = $block->getCategory();
        }

        $categories = $scopeCategory->getDescendantCategories();

        $products = $productRepository->findByCategorySort($categories, $sort, $count, $block->isIndividualSellersOnly());

        return $this->render('public/_partials/_home_category_block_products.html.twig', [
            'products' => $products,
        ]);
    }
}
