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
    #[Route('/categories', name: 'manage_categories_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(Request $request, CategoryRepository $repository): Response
    {
        $searchTerm = $request->query->get('q');
        $sortField = $request->query->get('sort', 'position');
        $sortDir = $request->query->get('dir', 'ASC');

        if ($searchTerm) {
            $categories = $repository->searchByName($searchTerm);
            $rows = $this->buildRows($categories, $repository);
            $rows = $this->sortRows($rows, $sortField, $sortDir);

            return $this->render('manage/categories/index.html.twig', [
                'rows' => $rows, 'parent' => null, 'breadcrumb' => [],
                'searchTerm' => $searchTerm, 'isSearchMode' => true,
                'currentSort' => $sortField, 'currentDir' => $sortDir,
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
        ]);
    }

    private function sortRows(array $rows, string $field, string $dir): array
    {
        $allowed = ['name', 'childrenCount', 'productCount'];
        if (!in_array($field, $allowed, true)) {
            $field = 'position';
            usort($rows, fn ($a, $b) => $a['category']->getPosition() <=> $b['category']->getPosition());
            return $rows;
        }
        $dirMultiplier = strtoupper($dir) === 'DESC' ? -1 : 1;

        usort($rows, function ($a, $b) use ($field, $dirMultiplier) {
            $valA = $field === 'name' ? $a['category']->getName() : $a[$field];
            $valB = $field === 'name' ? $b['category']->getName() : $b[$field];
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
        return $this->render('manage/categories/form.html.twig', [
            'category' => $category,
            'parent' => $category->getParent(),
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

        $categoryIds = array_map(fn ($c) => $c->getId(), $category->getDescendantCategories());
        $products = $productRepository->findByCategoryAdmin($categoryIds, $status, $sort, $term);

        return $this->render('manage/categories/products.html.twig', [
            'category' => $category,
            'products' => $products,
            'currentStatus' => $status,
            'currentSort' => $sort,
            'currentTerm' => $term,
        ]);
    }

    #[Route('/categories/{id}', name: 'manage_categories_show', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function show(Category $category, Request $request, CategoryRepository $repository): Response
    {
        $sortField = $request->query->get('sort', 'position');
        $sortDir = $request->query->get('dir', 'ASC');

        $children = $repository->findChildrenOf($category->getId());
        $childRows = $this->buildRows($children, $repository);
        $childRows = $this->sortRows($childRows, $sortField, $sortDir);

        return $this->render('manage/categories/show.html.twig', [
            'category' => $category,
            'childRows' => $childRows,
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