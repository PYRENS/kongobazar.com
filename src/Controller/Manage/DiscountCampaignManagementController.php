<?php

namespace App\Controller\Manage;

use App\Entity\AdministrativeUnit;
use App\Entity\Category;
use App\Entity\DiscountCampaign;
use App\Repository\DiscountCampaignRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SellerProfileRepository;
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
    public function index(Request $request, DiscountCampaignRepository $repository, CategoryRepository $categoryRepository, EntityManagerInterface $em): Response
    {
        $built = $this->buildFilteredCampaigns($request, $repository, $categoryRepository, $em);

        return $this->render('manage/discount_campaigns/index.html.twig', [
            'stats' => $built['stats'],
            'campaigns' => $built['campaigns'],
            'searchTerm' => $built['term'],
            'currentSort' => $built['sort'],
            'currentDir' => $built['dir'],
            'currentCategory' => $built['categoryId'],
            'currentProvince' => $built['unitId'],
            'currentSeller' => $built['sellerId'],
            'currentSellerName' => $built['sellerName'],
            'rootCategories' => $categoryRepository->findChildrenOf(null),
            'page' => $built['page'],
            'pages' => $built['pages'],
            'perPage' => $built['perPage'],
            'total' => $built['total'],
        ]);
    }

    #[Route('/remises/liste-fragment', name: 'manage_discount_campaigns_index_fragment', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function indexFragment(Request $request, DiscountCampaignRepository $repository, CategoryRepository $categoryRepository, EntityManagerInterface $em): Response
    {
        $built = $this->buildFilteredCampaigns($request, $repository, $categoryRepository, $em);

        return $this->json([
            'rowsHtml' => $this->renderView('manage/discount_campaigns/_index_rows.html.twig', [
                'campaigns' => $built['campaigns'],
            ]),
            'footerInfo' => $built['total'] . ' remise' . ($built['total'] != 1 ? 's' : '') . ' au total — page ' . $built['page'] . ' / ' . $built['pages'],
            'paginationHtml' => $this->renderView('manage/advertisements/_index_pagination.html.twig', ['page' => $built['page'], 'pages' => $built['pages']]),
        ]);
    }

    #[Route('/remises/rechercher-vendeurs', name: 'manage_discount_campaigns_search_sellers', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function searchSellers(Request $request, SellerProfileRepository $sellerProfileRepository): Response
    {
        $term = trim((string) $request->query->get('q', ''));
        $results = mb_strlen($term) >= 1 ? $sellerProfileRepository->searchByName($term) : [];

        return $this->json(['results' => array_map(fn ($s) => [
            'id' => $s->getId(),
            'name' => $s->getDisplayName(),
        ], $results)]);
    }

    #[Route('/remises/rechercher-titres', name: 'manage_discount_campaigns_search_titles', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function searchTitles(Request $request, ProductRepository $productRepository): Response
    {
        $term = trim((string) $request->query->get('q', ''));
        if (mb_strlen($term) < 1) {
            return $this->json(['results' => []]);
        }

        $qb = $productRepository->createQueryBuilder('p')
            ->andWhere('p.title LIKE :term')->setParameter('term', '%' . $term . '%')
            ->setMaxResults(15);

        if (preg_match('/(\d+)/', $term, $m)) {
            $kbzId = (int) ltrim($m[1], '0');
            if ($kbzId > 0) {
                $qb->orWhere('p.id = :kbzId')->setParameter('kbzId', $kbzId);
            }
        }

        $results = $qb->getQuery()->getResult();

        return $this->json(['results' => array_map(fn ($p) => [
            'id' => $p->getId(),
            'title' => $p->getTitle() . ' (' . $p->getKongobazarReference() . ')',
        ], $results)]);
    }

    /** Centralise filtrage/tri/pagination, réutilisé par la page complète et le fragment AJAX. */
    private function buildFilteredCampaigns(Request $request, DiscountCampaignRepository $repository, CategoryRepository $categoryRepository, EntityManagerInterface $em): array
    {
        $term = $request->query->get('q') ?: null;
        $sort = $request->query->get('sort', 'createdAt');
        $dir = strtoupper($request->query->get('dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $categoryId = $request->query->get('category') ? (int) $request->query->get('category') : null;
        $categoryIds = null;
        if ($categoryId) {
            $category = $categoryRepository->find($categoryId);
            $categoryIds = [$categoryId];
            if ($category) {
                foreach ($category->getDescendantCategories() as $descendant) {
                    $categoryIds[] = $descendant->getId();
                }
            }
        }

        $unitId = $request->query->get('province') ? (int) $request->query->get('province') : null;
        $unitIds = null;
        if ($unitId) {
            $unit = $em->getRepository(AdministrativeUnit::class)->find($unitId);
            $unitIds = $unit ? array_map(fn ($u) => $u->getId(), $unit->getDescendantUnits()) : [$unitId];
        }

        $sellerId = $request->query->get('seller') ? (int) $request->query->get('seller') : null;
        $sellerName = null;
        if ($sellerId) {
            $seller = $em->getRepository(\App\Entity\SellerProfile::class)->find($sellerId);
            $sellerName = $seller ? $seller->getDisplayName() : null;
        }

        $campaigns = $repository->findFiltered($term, $categoryIds, $unitIds, $sellerId, $sort, $dir);

        $stats = [
            'total' => count($campaigns),
            'active' => count(array_filter($campaigns, fn ($c) => 'active' === $c->getStatus())),
            'scheduled' => count(array_filter($campaigns, fn ($c) => 'scheduled' === $c->getStatus())),
        ];

        $perPage = in_array((int) $request->query->get('perPage', 20), [10, 20, 50, 100], true)
            ? (int) $request->query->get('perPage', 20) : 20;
        $page = max(1, (int) $request->query->get('page', 1));
        $total = count($campaigns);
        $campaigns = array_slice($campaigns, ($page - 1) * $perPage, $perPage);

        return [
            'campaigns' => $campaigns,
            'stats' => $stats,
            'term' => $term,
            'sort' => $sort,
            'dir' => $dir,
            'categoryId' => $categoryId,
            'unitId' => $unitId,
            'sellerId' => $sellerId,
            'sellerName' => $sellerName,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'perPage' => $perPage,
            'total' => $total,
        ];
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

            $startAt = 'now' === $request->request->get('start_mode', 'now')
                ? new \DateTimeImmutable()
                : new \DateTimeImmutable($request->request->get('start_at'));
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