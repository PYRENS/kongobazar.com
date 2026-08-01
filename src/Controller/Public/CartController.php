<?php

namespace App\Controller\Public;

use App\Repository\CartItemRepository;
use App\Repository\ProductVariantRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    #[Route('/panier', name: 'cart_index', host: 'kongobazar.com')]
    public function index(CartService $cartService): Response
    {
        $cart = $cartService->getCurrentCart();
        $summary = $cartService->getSummary($cart);

        return $this->render('public/cart.html.twig', [
            'summary' => $summary,
            'breadcrumbs' => [
                ['label' => 'Mon panier', 'url' => null],
            ],
        ]);
    }

    #[Route('/panier/ajouter/{id}', name: 'cart_add', host: 'kongobazar.com', methods: ['POST'])]
    public function add(int $id, Request $request, ProductVariantRepository $variantRepository, CartService $cartService): RedirectResponse
    {
        $variant = $variantRepository->find($id);
        if ($variant) {
            $quantity = max(1, (int) $request->request->get('quantity', 1));
            $cartService->addItem($variant, $quantity);
        }

        return $this->redirect($request->headers->get('referer', $this->generateUrl('public_home')));
    }

    #[Route('/panier/ajouter-ajax/{id}', name: 'cart_add_ajax', host: 'kongobazar.com', methods: ['POST'])]
    public function addAjax(int $id, Request $request, ProductVariantRepository $variantRepository, CartService $cartService): JsonResponse
    {
        $variant = $variantRepository->find($id);
        if (!$variant) {
            return new JsonResponse(['success' => false], 404);
        }

        $quantity = max(1, (int) $request->request->get('quantity', 1));
        $cartService->addItem($variant, $quantity);

        $cart = $cartService->getCurrentCart();
        $summary = $cartService->getSummary($cart);

        return new JsonResponse([
            'success' => true,
            'itemCount' => $summary['itemCount'],
            'subtotalUsd' => $summary['subtotalUsd'],
        ]);
    }

    #[Route('/panier/retirer/{id}', name: 'cart_remove', host: 'kongobazar.com', methods: ['POST'])]
    public function remove(int $id, Request $request, CartItemRepository $itemRepository, CartService $cartService): RedirectResponse
    {
        $item = $itemRepository->find($id);
        if ($item) {
            $cartService->removeItem($item);
        }

        return $this->redirect($request->headers->get('referer', $this->generateUrl('cart_index')));
    }

    #[Route('/panier/quantite/{id}', name: 'cart_update_quantity', host: 'kongobazar.com', methods: ['POST'])]
    public function updateQuantity(int $id, Request $request, CartItemRepository $itemRepository, CartService $cartService): RedirectResponse
    {
        $item = $itemRepository->find($id);
        if ($item) {
            $quantity = (int) $request->request->get('quantity', 1);
            $cartService->updateItemQuantity($item, $quantity);
        }

        return $this->redirect($request->headers->get('referer', $this->generateUrl('cart_index')));
    }

    #[Route('/panier/offcanvas-fragment', name: 'cart_offcanvas_fragment', host: 'kongobazar.com')]
    public function offcanvasFragment(): Response
    {
        return $this->render('public/_partials/_cart_offcanvas_body.html.twig');
    }

    #[Route('/panier/quantite-ajax/{id}', name: 'cart_update_quantity_ajax', host: 'kongobazar.com', methods: ['POST'])]
    public function updateQuantityAjax(
        int $id,
        Request $request,
        CartItemRepository $itemRepository,
        CartService $cartService,
    ): JsonResponse {
        $item = $itemRepository->find($id);
        if (!$item) {
            return new JsonResponse(['success' => false], 404);
        }

        $quantity = (int) $request->request->get('quantity', 1);

        if ($quantity <= 0) {
            $cartService->removeItem($item);
            $cart = $cartService->getCurrentCart();
            $summary = $cartService->getSummary($cart);
            $display = $cartService->getDisplaySubtotal($summary);

            return new JsonResponse([
                'success' => true,
                'removed' => true,
                'itemCount' => $summary['itemCount'],
                'displayAmount' => $display['amount'],
                'displayCurrency' => $display['currency'],
            ]);
        }

        $cartService->updateItemQuantity($item, $quantity);

        $cart = $item->getCart();
        $summary = $cartService->getSummary($cart);
        $display = $cartService->getDisplaySubtotal($summary);

        $lineTotal = null;
        foreach ($summary['lines'] as $line) {
            if ($line['item']->getId() === $item->getId()) {
                $lineTotal = $line['lineTotal'];
                break;
            }
        }

        return new JsonResponse([
            'success' => true,
            'removed' => false,
            'quantity' => $item->getQuantity(),
            'lineTotal' => $lineTotal,
            'itemCount' => $summary['itemCount'],
            'displayAmount' => $display['amount'],
            'displayCurrency' => $display['currency'],
        ]);
    }

    #[Route('/panier/retirer-ajax/{id}', name: 'cart_remove_ajax', host: 'kongobazar.com', methods: ['POST'])]
    public function removeAjax(int $id, CartItemRepository $itemRepository, CartService $cartService): JsonResponse
    {
        $item = $itemRepository->find($id);
        if (!$item) {
            return new JsonResponse(['success' => false], 404);
        }

        $cart = $item->getCart();
        $cartService->removeItem($item);

        $summary = $cartService->getSummary($cart);
        $display = $cartService->getDisplaySubtotal($summary);

        return new JsonResponse([
            'success' => true,
            'itemCount' => $summary['itemCount'],
            'displayAmount' => $display['amount'],
            'displayCurrency' => $display['currency'],
        ]);
    }



}