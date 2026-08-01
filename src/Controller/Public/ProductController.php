<?php

namespace App\Controller\Public;

use App\Repository\ProductRecommendationRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/produit/{slug}', name: 'catalog_product', host: 'kongobazar.com')]
    public function show(string $slug, ProductRepository $productRepository, ProductRecommendationRepository $recommendationRepository): Response
    {
        $product = $productRepository->findOneBy(['slug' => $slug]);

        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

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

        return $this->render('public/product.html.twig', [
            'product' => $product,
            'colors' => array_values($colors),
            'sizes' => array_values($sizes),
            'breadcrumbs' => $breadcrumbs,
            'recommendations' => $recommendations,
        ]);
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