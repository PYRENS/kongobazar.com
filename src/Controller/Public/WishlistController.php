<?php

namespace App\Controller\Public;

use App\Entity\WishlistItem;
use App\Repository\ProductVariantRepository;
use App\Repository\WishlistItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}