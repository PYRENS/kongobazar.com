<?php

namespace App\Twig;

use App\Repository\WishlistItemRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/** Fonction Twig is_wishlisted(product) : bool. Une seule requête par page, quel que soit le nombre de cartes. */
class WishlistExtension extends AbstractExtension
{
    private ?array $wishlistedIds = null; // null = pas encore chargé ; [] = chargé, vide

    public function __construct(
        private readonly Security $security,
        private readonly WishlistItemRepository $wishlistItemRepository,
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('is_wishlisted', [$this, 'isWishlisted'])];
    }

    public function isWishlisted($product): bool
    {
        $user = $this->security->getUser();
        if (!$user) {
            return false;
        }

        if (null === $this->wishlistedIds) {
            $this->wishlistedIds = $this->wishlistItemRepository->getWishlistedProductIds($user);
        }

        return in_array($product->getId(), $this->wishlistedIds, true);
    }
}