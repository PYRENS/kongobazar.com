<?php

namespace App\Controller\Public;

use App\Repository\ProductRecommendationRepository;
use App\Repository\ProductRepository;
use App\Service\SeoResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    private const RECENTLY_VIEWED_COOKIE = 'kb_recently_viewed';
    private const RECENTLY_VIEWED_MAX_STORED = 20;

    #[Route('/produit/{slug}', name: 'catalog_product', host: 'kongobazar.com')]
    public function show(string $slug, Request $request, ProductRepository $productRepository, ProductRecommendationRepository $recommendationRepository, SeoResolver $seoResolver, \Vich\UploaderBundle\Storage\StorageInterface $storage, \Doctrine\ORM\EntityManagerInterface $em): Response
    {
        $product = $productRepository->findOneBy(['slug' => $slug]);

        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        // Enregistre la vue, pour alimenter "Les plus consultés" et les stats de la fiche produit.
        $viewLog = new \App\Entity\ProductViewLog();
        $viewLog->setProduct($product);
        $viewLog->setViewedAt(new \DateTimeImmutable());
        $em->persist($viewLog);
        $em->flush();

        // "Consulté récemment" : historique par navigateur (cookie), pas lié au compte —
        // fonctionne aussi bien pour un visiteur non connecté.
        $recentIds = json_decode($request->cookies->get(self::RECENTLY_VIEWED_COOKIE, '[]'), true) ?: [];
        $recentIds = array_values(array_diff($recentIds, [$product->getId()])); // retire l'éventuelle occurrence existante
        array_unshift($recentIds, $product->getId());
        $recentIds = array_slice($recentIds, 0, self::RECENTLY_VIEWED_MAX_STORED);

        $recentlyViewedCookie = Cookie::create(self::RECENTLY_VIEWED_COOKIE)
            ->withValue(json_encode($recentIds))
            ->withExpires((new \DateTimeImmutable())->modify('+90 days'))
            ->withPath('/');

        // Couleurs et tailles distinctes réellement disponibles sur ce produit
        $colors = [];
        $sizes = [];
        foreach ($product->getVariants() as $variant) {
            if ($variant->getColor() && !isset($colors[$variant->getColor()->getId()])) {
                $colors[$variant->getColor()->getId()] = $variant->getColor();
            }
            if ($variant->getSize() && !isset($sizes[$variant->getSize()->getId()])) {
                $sizes[$variant->getSize()->getId()] = $variant->getSize();
            }
        }

        $breadcrumbs = [];
        if ($product->getCategory()) {
            foreach ($product->getCategory()->getAncestors() as $ancestor) {
                $breadcrumbs[] = [
                    'label' => $ancestor->getName(),
                    'url' => $this->generateUrl('catalog_category', ['slug' => $ancestor->getSlug()]),
                ];
            }
        }
        $breadcrumbs[] = ['label' => $product->getTitle(), 'url' => null];

        $recommendations = $product ? $recommendationRepository->findRecommendedProductsFor($product) : [];

        $priceLabel = $product->getBasePrice() . ' ' . $product->getCurrency();
        $firstImage = $product->getImages()->first();
        $seoData = $seoResolver->resolve('product', $product->getId(), null, [
            'metaTitle' => $product->getTitle() . ' — ' . $priceLabel . ' | KongoBazar',
            'metaDescription' => $product->getDescription()
                ? mb_substr(strip_tags($product->getDescription()), 0, 160)
                : ($product->getTitle() . ' à ' . $priceLabel . ' sur KongoBazar.'),
            'ogImageUrl' => $firstImage ? $storage->resolveUri($firstImage, 'imageFile') : null,
        ]);

        $response = $this->render('public/product.html.twig', [
            'seoData' => $seoData,
            'product' => $product,
            'colors' => array_values($colors),
            'sizes' => array_values($sizes),
            'breadcrumbs' => $breadcrumbs,
            'recommendations' => $recommendations,
        ]);
        $response->headers->setCookie($recentlyViewedCookie);

        return $response;
    }


    #[Route('/produit-variant/{slug}', name: 'catalog_product_find_variant', host: 'kongobazar.com')]
    public function findVariant(
        string $slug,
        \Symfony\Component\HttpFoundation\Request $request,
        ProductRepository $productRepository,
        \App\Repository\ProductVariantRepository $variantRepository,
    ): \Symfony\Component\HttpFoundation\JsonResponse {
        $product = $productRepository->findOneBy(['slug' => $slug]);
        if (!$product) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(['found' => false], 404);
        }

        $colorId = $request->query->get('color') ? (int) $request->query->get('color') : null;
        $sizeId = $request->query->get('size') ? (int) $request->query->get('size') : null;

        $variant = $variantRepository->findByProductColorSize($product, $colorId, $sizeId);

        if (!$variant) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(['found' => false]);
        }

        return new \Symfony\Component\HttpFoundation\JsonResponse([
            'found' => true,
            'variantId' => $variant->getId(),
            'stock' => $variant->getQuantity(),
            'inStock' => $variant->isInStock(),
        ]);
    }   
    
    
    #[Route('/produit/{slug}/suivre-disponibilite', name: 'product_follow_availability', host: 'kongobazar.com')]
    public function followAvailability(
        string $slug,
        ProductRepository $productRepository,
        \App\Repository\ProductAvailabilityAlertRepository $alertRepository,
        \Doctrine\ORM\EntityManagerInterface $em,
    ): \Symfony\Component\HttpFoundation\RedirectResponse {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('public_login');
        }

        $product = $productRepository->findOneBy(['slug' => $slug]);
        if ($product) {
            $existing = $alertRepository->findOneBy(['product' => $product, 'user' => $user]);
            if (!$existing) {
                $alert = new \App\Entity\ProductAvailabilityAlert();
                $alert->setProduct($product);
                $alert->setUser($user);
                $em->persist($alert);
                $em->flush();
            }
        }

        return $this->redirectToRoute('catalog_product', ['slug' => $slug]);
    }


}