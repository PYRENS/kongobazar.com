<?php

namespace App\Controller\Manage;

use App\Entity\DiscountCampaign;
use App\Repository\DiscountCampaignRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DiscountCampaignManagementController extends AbstractController
{
    #[Route('/remises', name: 'manage_discount_campaigns_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(DiscountCampaignRepository $repository): Response
    {
        return $this->render('manage/discount_campaigns/index.html.twig', [
            'campaigns' => $repository->findAllForAdmin(),
        ]);
    }

    #[Route('/remises/produits-recherche-json', name: 'manage_discount_campaigns_product_search', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function searchProducts(Request $request, ProductRepository $repository): JsonResponse
    {
        $term = trim((string) $request->query->get('q', ''));
        $results = mb_strlen($term) >= 2 ? $repository->searchInStockByTerm($term, 15) : [];

        return $this->json([
            'results' => array_map(fn ($p) => [
                'id' => $p->getId(),
                'name' => $p->getTitle() . ' — ' . $p->getBasePrice() . ' ' . $p->getCurrency(),
                'basePrice' => $p->getBasePrice(),
                'currency' => $p->getCurrency(),
            ], $results),
        ]);
    }

    #[Route('/remises/categories-en-stock-json', name: 'manage_discount_campaigns_categories_in_stock', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function categoriesInStock(Request $request, \App\Repository\CategoryRepository $categoryRepository): JsonResponse
    {
        $parentId = $request->query->get('parent_id') ? (int) $request->query->get('parent_id') : null;
        $categories = $categoryRepository->findChildrenWithInStockProducts($parentId);

        return $this->json([
            'results' => array_map(fn ($c) => ['id' => $c->getId(), 'name' => $c->getName(), 'hasChildren' => count($c->getChildren()) > 0], $categories),
        ]);
    }

    #[Route('/remises/produits-par-categorie-json', name: 'manage_discount_campaigns_products_by_category', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function productsByCategory(Request $request, ProductRepository $repository): JsonResponse
    {
        $categoryId = (int) $request->query->get('category_id');
        $products = $repository->findInStockByCategory($categoryId, 50);

        return $this->json([
            'results' => array_map(fn ($p) => [
                'id' => $p->getId(),
                'name' => $p->getTitle() . ' — ' . $p->getBasePrice() . ' ' . $p->getCurrency(),
            ], $products),
        ]);
    }

    #[Route('/remises/nouvelle', name: 'manage_discount_campaigns_new', host: 'manage.kongobazar.com', methods: ['GET', 'POST'])]
    public function new(Request $request, ProductRepository $productRepository, DiscountCampaignRepository $discountCampaignRepository, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $product = $productRepository->find((int) $request->request->get('product_id'));
            if (!$product) {
                $this->addFlash('error', 'Produit introuvable.');
                return $this->redirectToRoute('manage_discount_campaigns_new');
            }

            $existing = $discountCampaignRepository->findActiveOrScheduledForProduct($product);
            if ($existing) {
                $this->addFlash('error', 'Ce produit a déjà une remise en cours ou programmée. Modifie-la plutôt que d\'en créer une nouvelle.');
                return $this->redirectToRoute('manage_discount_campaigns_edit', ['id' => $existing->getId()]);
            }

            $discountedPrice = (float) $request->request->get('discounted_price');
            if ($discountedPrice <= 0 || $discountedPrice >= (float) $product->getBasePrice()) {
                $this->addFlash('error', 'Le prix remisé doit être strictement inférieur au prix normal du produit (' . $product->getBasePrice() . ' ' . $product->getCurrency() . ').');
                return $this->redirectToRoute('manage_discount_campaigns_new');
            }

            $startAt = new \DateTimeImmutable($request->request->get('start_at'));
            $endAt = new \DateTimeImmutable($request->request->get('end_at'));
            $now = new \DateTimeImmutable();

            if ($endAt <= $startAt) {
                $this->addFlash('error', 'La date de fin doit être postérieure à la date de début.');
                return $this->redirectToRoute('manage_discount_campaigns_new');
            }

            $campaign = new DiscountCampaign();
            $campaign->setProduct($product);
            $campaign->setMode($startAt <= $now ? 'immediate' : 'scheduled');
            $campaign->setDiscountedPrice((string) $request->request->get('discounted_price'));
            $campaign->setStartAt($startAt);
            $campaign->setEndAt($endAt);
            $campaign->setStatus($startAt <= $now && $endAt > $now ? 'active' : 'scheduled');

            $em->persist($campaign);
            $em->flush();

            $this->addFlash('success', 'Remise créée.');
            return $this->redirectToRoute('manage_discount_campaigns_index');
        }

        return $this->render('manage/discount_campaigns/form.html.twig');
    }

    #[Route('/remises/{id}/annuler', name: 'manage_discount_campaigns_cancel', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(int $id, DiscountCampaignRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $campaign = $repository->find($id);
        if ($campaign) {
            $campaign->setStatus('cancelled');
            $em->flush();
        }

        return $this->redirectToRoute('manage_discount_campaigns_index');
    }

    #[Route('/remises/{id}/modifier', name: 'manage_discount_campaigns_edit', host: 'manage.kongobazar.com', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request, DiscountCampaignRepository $repository, EntityManagerInterface $em): Response
    {
        $campaign = $repository->find($id);
        if (!$campaign) {
            throw $this->createNotFoundException();
        }

        if ($request->isMethod('POST')) {
            $discountedPrice = (float) $request->request->get('discounted_price');
            if ($discountedPrice <= 0 || $discountedPrice >= (float) $campaign->getProduct()->getBasePrice()) {
                $this->addFlash('error', 'Le prix remisé doit être strictement inférieur au prix normal du produit.');
                return $this->redirectToRoute('manage_discount_campaigns_edit', ['id' => $id]);
            }

            $startAt = new \DateTimeImmutable($request->request->get('start_at'));
            $endAt = new \DateTimeImmutable($request->request->get('end_at'));
            $now = new \DateTimeImmutable();

            if ($endAt <= $startAt) {
                $this->addFlash('error', 'La date de fin doit être postérieure à la date de début.');
                return $this->redirectToRoute('manage_discount_campaigns_edit', ['id' => $id]);
            }

            $campaign->setDiscountedPrice((string) $discountedPrice);
            $campaign->setStartAt($startAt);
            $campaign->setEndAt($endAt);
            $campaign->setStatus($startAt <= $now && $endAt > $now ? 'active' : ($endAt <= $now ? 'expired' : 'scheduled'));

            $em->flush();

            $this->addFlash('success', 'Remise mise à jour.');
            return $this->redirectToRoute('manage_discount_campaigns_index');
        }

        return $this->render('manage/discount_campaigns/edit.html.twig', ['campaign' => $campaign]);
    }
}