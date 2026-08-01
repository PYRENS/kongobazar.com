<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\ProductVariant;
use App\Entity\User;
use App\Repository\CartItemRepository;
use App\Repository\CartRepository;
use App\Repository\ExchangeRateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{

    private ?string $pendingCartToken = null;

    public function __construct(
        private readonly CartRepository $cartRepository,
        private readonly CartItemRepository $cartItemRepository,
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly ExchangeRateRepository $exchangeRateRepository,
    ) {
    }

    public function getCurrentCart(): Cart
    {
        $user = $this->security->getUser();

        if ($user instanceof User) {
            $cart = $this->cartRepository->findOneBy(['user' => $user]);
            if (!$cart) {
                $cart = new Cart();
                $cart->setUser($user);
                $this->em->persist($cart);
                $this->em->flush();
            }
            return $cart;
        }

        // Panier invité : jeton stocké dans un cookie longue durée (30 jours),
        // pas juste la session PHP qui expire trop vite pour ce contexte
        $request = $this->requestStack->getCurrentRequest();
        $token = $request?->cookies->get('kb_cart_token');

        if ($token) {
            $cart = $this->cartRepository->findOneBy(['sessionToken' => $token]);
            if ($cart) {
                return $cart;
            }
        }

        $token = bin2hex(random_bytes(32));
        $this->pendingCartToken = $token; // sera posé en cookie par CartCookieListener

        $cart = new Cart();
        $cart->setSessionToken($token);
        $this->em->persist($cart);
        $this->em->flush();

        return $cart;
    }

    public function addItem(ProductVariant $variant, int $quantity = 1): CartItem
    {
        $cart = $this->getCurrentCart();
        $stock = $variant->getQuantity();

        $existing = $this->cartItemRepository->findOneBy(['cart' => $cart, 'variant' => $variant]);

        if ($existing) {
            $newQty = min($existing->getQuantity() + $quantity, max(1, $stock));
            $existing->setQuantity($newQty);
            $this->em->flush();
            return $existing;
        }

        $item = new CartItem();
        $item->setCart($cart);
        $item->setVariant($variant);
        $item->setQuantity(min($quantity, max(1, $stock)));
        $this->em->persist($item);
        $cart->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $item;
    }

    public function updateItemQuantity(CartItem $item, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeItem($item);
            return;
        }

        $stock = $item->getVariant()->getQuantity();
        $item->setQuantity(min($quantity, max(1, $stock)));
        $this->em->flush();
    }

    public function removeItem(CartItem $item): void
    {
        $this->em->remove($item);
        $this->em->flush();
    }

    /**
     * Calcule le résumé complet du panier : prix courant de chaque ligne
     * (solde/discount actif pris en compte, jamais le prix figé), converti
     * en USD pour le sous-total global.
     */
    public function getSummary(Cart $cart): array
    {
        $lines = [];
        $subtotalUsd = '0';
        $itemCount = 0;

        foreach ($cart->getItems() as $item) {
            $variant = $item->getVariant();
            $product = $variant->getProduct();

            $unitPrice = $product->getCurrentDiscountedPrice() ?? $product->getBasePrice();
            $lineTotal = bcmul($unitPrice, (string) $item->getQuantity(), 2);

            $lineTotalUsd = $product->getCurrency() === 'USD'
                ? $lineTotal
                : $this->convertToUsd($lineTotal, $product->getCurrency());

            $subtotalUsd = bcadd($subtotalUsd, $lineTotalUsd, 2);
            $itemCount += $item->getQuantity();

            $lines[] = [
                'item' => $item,
                'unitPrice' => $unitPrice,
                'currency' => $product->getCurrency(),
                'lineTotal' => $lineTotal,
                'available' => $item->getQuantity() <= $variant->getQuantity(),
            ];
        }

        return [
            'lines' => $lines,
            'itemCount' => $itemCount,
            'subtotalUsd' => $subtotalUsd,
        ];
    }

    private function convertToUsd(string $amount, string $fromCurrency): string
    {
        if ($fromCurrency === 'USD') {
            return $amount;
        }

        $rate = $this->exchangeRateRepository->findCurrentRate();
        if (!$rate) {
            return $amount; // repli sûr : pas de taux dispo, pas de conversion plutôt qu'un crash
        }

        return bcdiv($amount, $rate->getRateUsdToCdf(), 2);
    }

    /**
     * Fusionne le panier invité vers le compte, appelé juste après une connexion réussie.
     */
    public function mergeGuestCartIntoUser(string $guestToken, User $user): void
    {
        $guestCart = $this->cartRepository->findOneBy(['sessionToken' => $guestToken]);
        if (!$guestCart) {
            return;
        }

        $userCart = $this->cartRepository->findOneBy(['user' => $user]);

        if (!$userCart) {
            $guestCart->setUser($user);
            $guestCart->setSessionToken(null);
            $this->em->flush();
            return;
        }

        foreach ($guestCart->getItems() as $guestItem) {
            $existing = $this->cartItemRepository->findOneBy(['cart' => $userCart, 'variant' => $guestItem->getVariant()]);
            if ($existing) {
                $stock = $guestItem->getVariant()->getQuantity();
                $existing->setQuantity(min($existing->getQuantity() + $guestItem->getQuantity(), max(1, $stock)));
                $this->em->remove($guestItem);
            } else {
                $guestItem->setCart($userCart);
            }
        }

        $this->em->remove($guestCart);
        $this->em->flush();
    }

    /**
     * Convertir l'affichage selon le devise.
     */    
    public function getDisplaySubtotal(array $summary): array
    {
        $currency = $this->requestStack->getSession()->get('_currency', 'USD');

        if ($currency === 'USD') {
            return ['amount' => $summary['subtotalUsd'], 'currency' => 'USD'];
        }

        $rate = $this->exchangeRateRepository->findCurrentRate();
        if (!$rate) {
            return ['amount' => $summary['subtotalUsd'], 'currency' => 'USD']; // repli si aucun taux dispo
        }

        $amountCdf = bcmul($summary['subtotalUsd'], $rate->getRateUsdToCdf(), 2);
        return ['amount' => $amountCdf, 'currency' => 'CDF'];
    }


    public function getPendingCartToken(): ?string
    {
        return $this->pendingCartToken;
    }

    
}