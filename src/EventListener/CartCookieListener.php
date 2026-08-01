<?php

namespace App\EventListener;

use App\Service\CartService;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CartCookieListener implements EventSubscriberInterface
{
    public function __construct(private readonly CartService $cartService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $token = $this->cartService->getPendingCartToken();
        if (!$token) {
            return;
        }

        $cookie = Cookie::create('kb_cart_token')
            ->withValue($token)
            ->withExpires(new \DateTimeImmutable('+30 days'))
            ->withHttpOnly(true)
            ->withSameSite('lax');

        $event->getResponse()->headers->setCookie($cookie);
    }
}