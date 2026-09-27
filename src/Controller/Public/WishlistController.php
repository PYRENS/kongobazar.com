<?php

namespace App\Controller\Public;

use App\Entity\WishlistItem;
use App\Repository\ProductRepository;
use App\Repository\ProductVariantRepository;
use App\Repository\WishlistItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class WishlistController extends AbstractController
{
    #[Route('/wishlist/ajouter/{variantId}', name: 'wishlist_add', host: 'kongobazar.com')]
    public function add(
        int $variantId,
        ProductVariantRepository $variantRepository,
        WishlistItemRepository $wishlistItemRepository,
        EntityManagerInterface $em,
    ): RedirectResponse {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('public_login');
        }

        $variant = $variantRepository->find($variantId);
        if ($variant) {
            $existing = $wishlistItemRepository->findOneBy(['user' => $user, 'variant' => $variant]);
            if (!$existing) {
                $item = new WishlistItem();
                $item->setUser($user);
                $item->setVariant($variant);
                $em->persist($item);
                $em->flush();
            }
        }

        return $this->redirectToRoute('wishlist_index');
    }

    /**
     * Ajoute/retire un PRODUIT (pas une variante précise) de la liste de souhaits, depuis les
     * cartes de l'accueil. Résout automatiquement la variante, comme pour l'ajout au panier.
     */
    #[Route('/wishlist/basculer-ajax/{productId}', name: 'wishlist_toggle_ajax', host: 'kongobazar.com', methods: ['POST'])]
    public function toggleAjax(
        int $productId,
        ProductRepository $productRepository,
        WishlistItemRepository $wishlistItemRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'redirect' => $this->generateUrl('public_login')]);
        }

        $product = $productRepository->find($productId);
        if (!$product) {
            return new JsonResponse(['success' => false, 'error' => 'Produit introuvable.'], 404);
        }

        $variant = null;
        foreach ($product->getVariants() as $candidate) {
            if ($candidate->isInStock()) {
                $variant = $candidate;
                break;
            }
        }
        if (!$variant && $product->getVariants()->count() > 0) {
            $variant = $product->getVariants()->first();
        }
        if (!$variant) {
            return new JsonResponse(['success' => false, 'error' => 'Produit indisponible.'], 422);
        }

        $existing = $wishlistItemRepository->findOneBy(['user' => $user, 'variant' => $variant]);
        if ($existing) {
            $em->remove($existing);
            $em->flush();
            return new JsonResponse(['success' => true, 'added' => false]);
        }

        if ($wishlistItemRepository->countForUser($user) >= 20) {
            return new JsonResponse(['success' => false, 'limitReached' => true]);
        }

        $item = new WishlistItem();
        $item->setUser($user);
        $item->setVariant($variant);
        $em->persist($item);
        $em->flush();

        return new JsonResponse(['success' => true, 'added' => true]);
    }
}