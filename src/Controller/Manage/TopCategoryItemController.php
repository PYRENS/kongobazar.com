<?php

namespace App\Controller\Manage;

use App\Entity\Category;
use App\Entity\TopCategoryItem;
use App\Repository\CategoryRepository;
use App\Repository\TopCategoryItemRepository;
use App\Repository\TopCategorySectionSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TopCategoryItemController extends AbstractController
{
    #[Route('/parametres/top-categorie-accueil', name: 'manage_top_category_items_setting', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(TopCategoryItemRepository $repository, TopCategorySectionSettingRepository $sectionRepository): Response
    {
        return $this->render('manage/top_category_items/index.html.twig', [
            'items' => $repository->findAllOrdered(),
            'sectionSetting' => $sectionRepository->getSingleton(),
        ]);
    }

    #[Route('/parametres/top-categorie-accueil/basculer', name: 'manage_top_category_items_toggle_enabled', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function toggleEnabled(TopCategorySectionSettingRepository $repository, EntityManagerInterface $em): Response
    {
        $setting = $repository->getSingleton();
        $setting->setEnabled(!$setting->isEnabled());
        $em->flush();

        return $this->json(['ok' => true, 'enabled' => $setting->isEnabled()]);
    }

    #[Route('/parametres/top-categorie-accueil/ajouter', name: 'manage_top_category_items_add', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function add(Request $request, TopCategoryItemRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $categoryId = (int) $request->request->get('category_id');
        $category = $em->getRepository(Category::class)->find($categoryId);

        if (!$category) {
            $this->addFlash('error', 'Catégorie introuvable.');
            return $this->redirectToRoute('manage_top_category_items_setting');
        }

        $existing = $em->getRepository(TopCategoryItem::class)->findOneBy(['category' => $category]);
        if ($existing) {
            $this->addFlash('error', 'Cette catégorie est déjà dans le carrousel.');
            return $this->redirectToRoute('manage_top_category_items_setting');
        }

        $item = new TopCategoryItem();
        $item->setCategory($category);
        $item->setPosition($repository->findNextPosition());
        $em->persist($item);
        $em->flush();

        $this->addFlash('success', 'Catégorie ajoutée au carrousel.');
        return $this->redirectToRoute('manage_top_category_items_setting');
    }

    #[Route('/parametres/top-categorie-accueil/{id}/supprimer', name: 'manage_top_category_items_remove', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function remove(TopCategoryItem $item, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($item);
        $em->flush();

        $this->addFlash('success', 'Catégorie retirée.');
        return $this->redirectToRoute('manage_top_category_items_setting');
    }

    #[Route('/parametres/top-categorie-accueil/{id}/deplacer/{direction}', name: 'manage_top_category_items_move', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function move(TopCategoryItem $item, string $direction, TopCategoryItemRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $items = $repository->findAllOrdered();
        $index = array_search($item->getId(), array_map(fn ($i) => $i->getId(), $items), true);
        $swapWith = 'up' === $direction ? $index - 1 : $index + 1;

        if ($swapWith >= 0 && $swapWith < count($items)) {
            $a = $items[$index]->getPosition();
            $b = $items[$swapWith]->getPosition();
            $items[$index]->setPosition($b);
            $items[$swapWith]->setPosition($a);
            $em->flush();
        }

        return $this->redirectToRoute('manage_top_category_items_setting');
    }

    #[Route('/parametres/top-categorie-accueil/{id}/couleur', name: 'manage_top_category_items_update_color', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateColor(TopCategoryItem $item, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $color = $request->request->get('background_color');
        $item->setBackgroundColor($color ?: null);
        $em->flush();

        $this->addFlash('success', 'Couleur mise à jour.');
        return $this->redirectToRoute('manage_top_category_items_setting');
    }

    /** Cascade générique — enfants d'une catégorie, n'importe quel niveau (même endpoint que Blocs catégorie, réutilisable). */
    #[Route('/parametres/top-categorie-accueil/categories-enfants', name: 'manage_top_category_items_children', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function childrenCategories(Request $request, CategoryRepository $categoryRepository): Response
    {
        $parentId = $request->query->get('parent_id') ? (int) $request->query->get('parent_id') : null;
        $children = $categoryRepository->findChildrenOf($parentId);

        return $this->json(['results' => array_map(fn (Category $c) => [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'hasChildren' => count($categoryRepository->findChildrenOf($c->getId())) > 0,
        ], $children)]);
    }
}
