<?php

namespace App\Controller\Manage;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

class CategoryManagementController extends AbstractController
{
    public function __construct(
        private readonly \App\Repository\CategoryAttributeRepository $categoryAttributeRepository,
    ) {
    }
    #[Route('/categories', name: 'manage_categories_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(Request $request, CategoryRepository $repository): Response
    {
        $searchTerm = $request->query->get('q');
        $sortField = $request->query->get('sort', 'name');
        $sortDir = $request->query->get('dir', 'ASC');

        if ($searchTerm) {
            $categories = $repository->searchByName($searchTerm);
            $rows = $this->buildRows($categories, $repository);
            $rows = $this->sortRows($rows, $sortField, $sortDir);

            return $this->render('manage/categories/index.html.twig', [
                'rows' => $rows, 'parent' => null, 'breadcrumb' => [],
                'searchTerm' => $searchTerm, 'isSearchMode' => true,
                'currentSort' => $sortField, 'currentDir' => $sortDir,
                'totalCategoriesCount' => $repository->countAll(),
                'rootCategoriesCount' => count($repository->findRootCategories()),
            ]);
        }

        $parentId = $request->query->get('parent') ? (int) $request->query->get('parent') : null;
        $parent = $parentId ? $repository->find($parentId) : null;

        $breadcrumb = [];
        $walker = $parent;
        while ($walker) {
            array_unshift($breadcrumb, $walker);
            $walker = $walker->getParent();
        }

        $children = $repository->findChildrenOf($parentId);
        $rows = $this->buildRows($children, $repository);
        $rows = $this->sortRows($rows, $sortField, $sortDir);

        return $this->render('manage/categories/index.html.twig', [
            'rows' => $rows, 'parent' => $parent, 'breadcrumb' => $breadcrumb,
            'searchTerm' => null, 'isSearchMode' => false,
            'currentSort' => $sortField, 'currentDir' => $sortDir,
            'totalCategoriesCount' => $repository->countAll(),
            'rootCategoriesCount' => count($repository->findRootCategories()),
        ]);
    }

    private function sortRows(array $rows, string $field, string $dir): array
    {
        $allowed = ['name', 'childrenCount', 'productCount', 'position'];
        if (!in_array($field, $allowed, true)) {
            $field = 'name';
        }
        $dirMultiplier = strtoupper($dir) === 'DESC' ? -1 : 1;

        // Tri alphabétique "vrai" (locale française) plutôt qu'octet par octet — sinon les accents
        // (É, È, À...) se classent après Z au lieu d'à côté de leur lettre de base.
        $collator = class_exists(\Collator::class) ? new \Collator('fr_FR') : null;

        usort($rows, function ($a, $b) use ($field, $dirMultiplier, $collator) {
            if ($field === 'name') {
                $valA = $a['category']->getName();
                $valB = $b['category']->getName();
                $cmp = $collator ? $collator->compare($valA, $valB) : strcasecmp($valA, $valB);
                return $dirMultiplier * $cmp;
            }
            $valA = $field === 'position' ? $a['category']->getPosition() : $a[$field];
            $valB = $field === 'position' ? $b['category']->getPosition() : $b[$field];
            return $dirMultiplier * ($valA <=> $valB);
        });

        return $rows;
    }

    private function buildRows(array $categories, CategoryRepository $repository): array
    {
        return array_map(fn ($cat) => [
            'category' => $cat,
            'childrenCount' => $repository->countChildrenOf($cat->getId()),
            'productCount' => $repository->countProductsIn(array_map(fn ($c) => $c->getId(), $cat->getDescendantCategories())),
            'attributeCount' => count($this->categoryAttributeRepository->findByCategory($cat->getId())),
        ], $categories);
    }

    #[Route('/categories/nouveau', name: 'manage_categories_new', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function new(Request $request, CategoryRepository $repository): Response
    {
        $parentId = $request->query->get('parent') ? (int) $request->query->get('parent') : null;
        $parent = $parentId ? $repository->find($parentId) : null;

        return $this->render('manage/categories/form.html.twig', [
            'category' => null,
            'parent' => $parent,
            'rootCategories' => $repository->findRootCategories(),
        ]);
    }

    #[Route('/categories/nouveau', name: 'manage_categories_create', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function create(Request $request, CategoryRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $parentId = $request->request->get('parent_id') ? (int) $request->request->get('parent_id') : null;
        $parent = $parentId ? $repository->find($parentId) : null;

        $category = new Category();
        $this->hydrate($category, $request, $em);
        $category->setParent($parent);

        $slugger = new AsciiSlugger();
        $category->setSlug(strtolower($slugger->slug($category->getName())) . '-' . uniqid());

        $em->persist($category);
        $em->flush();

        $this->addFlash('success', $category->getName() . ' créée.');
        return $this->redirectToRoute('manage_categories_index', $parentId ? ['parent' => $parentId] : []);
    }

    #[Route('/categories/{id}/modifier', name: 'manage_categories_edit', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function edit(Category $category, CategoryRepository $repository): Response
    {
        $parent = $category->getParent();
        $ancestorIds = [];
        $node = $parent;
        while ($node) {
            array_unshift($ancestorIds, $node->getId());
            $node = $node->getParent();
        }

        return $this->render('manage/categories/form.html.twig', [
            'category' => $category,
            'parent' => $parent,
            'parentAncestorIds' => implode(',', $ancestorIds),
            'rootCategories' => $repository->findRootCategories($category->getId()),
        ]);
    }

    #[Route('/categories/{id}/modifier', name: 'manage_categories_update', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function update(Category $category, Request $request, EntityManagerInterface $em, CategoryRepository $repository): RedirectResponse
    {
        $this->hydrate($category, $request, $em);

        $parentId = $request->request->get('parent_id') ? (int) $request->request->get('parent_id') : null;
        $newParent = $parentId ? $repository->find($parentId) : null;

        if ($this->wouldCreateCycle($category, $newParent)) {
            $this->addFlash('error', 'Impossible : une catégorie ne peut pas être rattachée à elle-même ou à l\'une de ses sous-catégories.');
            return $this->redirectToRoute('manage_categories_edit', ['id' => $category->getId()]);
        }

        $category->setParent($newParent);
        $em->flush();

        $this->addFlash('success', $category->getName() . ' mise à jour.');
        $parentId = $category->getParent()?->getId();
        return $this->redirectToRoute('manage_categories_index', $parentId ? ['parent' => $parentId] : []);
    }

    private function wouldCreateCycle(Category $category, ?Category $newParent): bool
    {
        $current = $newParent;
        while ($current !== null) {
            if ($current->getId() === $category->getId()) {
                return true;
            }
            $current = $current->getParent();
        }
        return false;
    }

    private function hydrate(Category $category, Request $request, EntityManagerInterface $em): void
    {
        $category->setName($request->request->get('name'));
        $category->setIcon($request->request->get('icon') ?: null);
        $category->setThemeColor($request->request->get('theme_color') ?: null);
        $category->setPosition((int) $request->request->get('position', 0));
        $category->setRequiresModel((bool) $request->request->get('requires_model'));
        $category->setAuthenticityRelevant((bool) $request->request->get('authenticity_relevant'));
        $category->setIsVehiclePart((bool) $request->request->get('is_vehicle_part'));
        $category->setIsAutoPartRoot((bool) $request->request->get('is_auto_part_root'));
        $category->setIsMotoPartRoot((bool) $request->request->get('is_moto_part_root'));
        $category->setIsAutoAccessoryRoot((bool) $request->request->get('is_auto_accessory_root'));
        $category->setIsMotoAccessoryRoot((bool) $request->request->get('is_moto_accessory_root'));
        $category->setIsAutoOfferRoot((bool) $request->request->get('is_auto_offer_root'));
        $category->setIsMotoOfferRoot((bool) $request->request->get('is_moto_offer_root'));

        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $category->setImageFile($imageFile);
        }
    }

    #[Route('/categories/{id}/supprimer', name: 'manage_categories_delete', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function delete(Category $category, EntityManagerInterface $em): RedirectResponse
    {
        $name = $category->getName();
        $parentId = $category->getParent()?->getId();

        $em->remove($category);
        $em->flush();

        $this->addFlash('warning', $name . ' et ses sous-catégories ont été supprimées.');
        return $this->redirectToRoute('manage_categories_index', $parentId ? ['parent' => $parentId] : []);
    }

    #[Route('/categories/{id}/produits', name: 'manage_categories_products', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function products(Category $category, Request $request, ProductRepository $productRepository): Response
    {
        $status = $request->query->get('status');
        $sort = $request->query->get('sort', 'newest');
        $term = $request->query->get('q');
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = in_array((int) $request->query->get('perPage', 20), [10, 20, 50, 100], true)
            ? (int) $request->query->get('perPage', 20)
            : 20;

        $categoryIds = array_map(fn ($c) => $c->getId(), $category->getDescendantCategories());
        $products = $productRepository->findByCategoryAdmin($categoryIds, $status, $sort, $term, $page, $perPage);
        $total = $productRepository->countByCategoryAdmin($categoryIds, $status, $term);

        return $this->render('manage/categories/products.html.twig', [
            'category' => $category,
            'products' => $products,
            'currentStatus' => $status,
            'currentSort' => $sort,
            'currentTerm' => $term,
            'page' => $page,
            'perPage' => $perPage,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'total' => $total,
            'stats' => $productRepository->getCategoryAdminStats($categoryIds),
        ]);
    }
    #[Route('/categories/produits/{id}/bloquer', name: 'manage_categories_product_toggle_block', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleProductBlock(\App\Entity\Product $product, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $product->setStatus($product->getStatus() === 'suspended' ? 'active' : 'suspended');
        $em->flush();

        $this->addFlash('success', $product->getTitle() . ' — statut mis à jour : ' . ($product->getStatus() === 'suspended' ? 'bloqué' : 'actif'));

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('manage_categories_index');
    }

    #[Route('/categories/{id}', name: 'manage_categories_show', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function show(Category $category, Request $request, CategoryRepository $repository, \App\Repository\ProductRepository $productRepository): Response
    {
        $ancestors = [];
        $node = $category;
        while ($node) {
            array_unshift($ancestors, $node->getName());
            $node = $node->getParent();
        }
        $sortField = $request->query->get('sort', 'name');
        $sortDir = $request->query->get('dir', 'ASC');
        $searchTerm = $request->query->get('q');
        $allowedPerPage = [10, 20, 50, 100];
        $childPerPage = in_array((int) $request->query->get('cperpage', 20), $allowedPerPage, true)
            ? (int) $request->query->get('cperpage', 20) : 20;
        $childPage = max(1, (int) $request->query->get('cpage', 1));

        $productCategoryIds = array_merge(
            [$category->getId()],
            array_map(fn ($c) => $c->getId(), $category->getDescendantCategories())
        );
        $productTerm = $request->query->get('pq') ?: null;
        $productStatus = $request->query->get('pstatus') ?: null;
        $productCondition = $request->query->get('pcondition') ?: null;
        $productPerPage = in_array((int) $request->query->get('pperpage', 20), $allowedPerPage, true)
            ? (int) $request->query->get('pperpage', 20) : 20;
        $productPage = max(1, (int) $request->query->get('ppage', 1));

        $productTotal = $productRepository->countByCategoryScope($productCategoryIds, $productTerm, $productStatus, $productCondition);
        $products = $productRepository->findByCategoryScope($productCategoryIds, $productTerm, $productStatus, $productCondition, $productPerPage, ($productPage - 1) * $productPerPage);

        $allChildren = $repository->findChildrenOf($category->getId());
        if ($searchTerm) {
            $allChildren = array_values(array_filter(
                $allChildren,
                fn (Category $c) => str_contains(mb_strtolower($c->getName()), mb_strtolower($searchTerm))
            ));
        }

        $childRows = $this->buildRows($allChildren, $repository);
        $childRows = $this->sortRows($childRows, $sortField, $sortDir);

        $childTotal = count($childRows);
        $childRows = array_slice($childRows, ($childPage - 1) * $childPerPage, $childPerPage);

        return $this->render('manage/categories/show.html.twig', [
            'category' => $category,
            'categoryAncestorNames' => $ancestors,
            'childRows' => $childRows,
            'searchTerm' => $searchTerm,
            'childPage' => $childPage,
            'childPages' => max(1, (int) ceil($childTotal / $childPerPage)),
            'childTotal' => $childTotal,
            'childPerPage' => $childPerPage,
            'products' => $products,
            'productTotal' => $productTotal,
            'productPage' => $productPage,
            'productPages' => max(1, (int) ceil($productTotal / $productPerPage)),
            'productPerPage' => $productPerPage,
            'productTerm' => $productTerm,
            'productStatus' => $productStatus,
            'productCondition' => $productCondition,
            'productCount' => $repository->countProductsIn(array_map(fn ($c) => $c->getId(), $category->getDescendantCategories())),
            'currentSort' => $sortField,
            'currentDir' => $sortDir,
        ]);
    }

    #[Route('/categories/{id}/basculer', name: 'manage_categories_toggle', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function toggle(Category $category, EntityManagerInterface $em): RedirectResponse
    {
        $category->setActive(!$category->isActive());
        $em->flush();

        $this->addFlash('success', $category->getName() . ' — ' . ($category->isActive() ? 'activée' : 'désactivée') . '.');
        return $this->redirectToRoute('manage_categories_show', ['id' => $category->getId()]);
    }

}