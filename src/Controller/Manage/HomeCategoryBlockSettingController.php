<?php

namespace App\Controller\Manage;

use App\Entity\Category;
use App\Entity\HomeCategoryBlockSetting;
use App\Entity\HomeCategoryBlockSortTab;
use App\Entity\HomeCategoryBlockSubcategory;
use App\Repository\CategoryRepository;
use App\Repository\HomeCategoryBlockSectionSettingRepository;
use App\Repository\HomeCategoryBlockSettingRepository;
use App\Repository\HomeCategoryBlockSortTabRepository;
use App\Repository\HomeCategoryBlockSubcategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeCategoryBlockSettingController extends AbstractController
{
    #[Route('/parametres/blocs-categorie-accueil', name: 'manage_home_category_blocks_setting', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(HomeCategoryBlockSettingRepository $repository, HomeCategoryBlockSectionSettingRepository $sectionRepository, \App\Repository\AdvertisementRepository $advertisementRepository): Response
    {
        $blocks = $repository->findAllOrdered();

        $bannersByBlockId = [];
        foreach ($blocks as $block) {
            $bannersByBlockId[$block->getId()] = $advertisementRepository->findOneActiveByZoneAndCategory('category_block_banner', $block->getCategory(), 'public');
        }

        return $this->render('manage/home_category_blocks_setting/index.html.twig', [
            'blocks' => $blocks,
            'sectionSetting' => $sectionRepository->getSingleton(),
            'bannersByBlockId' => $bannersByBlockId,
        ]);
    }

    #[Route('/parametres/blocs-categorie-accueil/{id}/basculer-bloc', name: 'manage_home_category_blocks_toggle_block', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleBlock(HomeCategoryBlockSetting $block, EntityManagerInterface $em): Response
    {
        $block->setEnabled(!$block->isEnabled());
        $em->flush();

        return $this->json(['ok' => true, 'enabled' => $block->isEnabled()]);
    }

    #[Route('/parametres/blocs-categorie-accueil/{id}/particuliers-basculer', name: 'manage_home_category_blocks_toggle_individual', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function toggleIndividualOnly(HomeCategoryBlockSetting $block, EntityManagerInterface $em): Response
    {
        $block->setIndividualSellersOnly(!$block->isIndividualSellersOnly());
        $em->flush();

        return $this->json(['ok' => true, 'enabled' => $block->isIndividualSellersOnly()]);
    }

    #[Route('/parametres/blocs-categorie-accueil/{id}/banniere-basculer', name: 'manage_home_category_blocks_toggle_banner', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function toggleBanner(HomeCategoryBlockSetting $block, EntityManagerInterface $em): Response
    {
        $block->setBannerEnabled(!$block->isBannerEnabled());
        $em->flush();

        return $this->json(['ok' => true, 'enabled' => $block->isBannerEnabled()]);
    }

    #[Route('/parametres/blocs-categorie-accueil/basculer', name: 'manage_home_category_blocks_toggle_enabled', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function toggleEnabled(HomeCategoryBlockSectionSettingRepository $repository, EntityManagerInterface $em): Response
    {
        $setting = $repository->getSingleton();
        $setting->setEnabled(!$setting->isEnabled());
        $em->flush();

        return $this->json(['ok' => true, 'enabled' => $setting->isEnabled()]);
    }

    #[Route('/parametres/blocs-categorie-accueil/ajouter', name: 'manage_home_category_blocks_add', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function addBlock(Request $request, HomeCategoryBlockSettingRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $categoryId = (int) $request->request->get('category_id');
        $category = $em->getRepository(Category::class)->find($categoryId);

        if (!$category) {
            $this->addFlash('error', 'Catégorie introuvable.');
            return $this->redirectToRoute('manage_home_category_blocks_setting');
        }

        $existing = $em->getRepository(HomeCategoryBlockSetting::class)->findOneBy(['category' => $category]);
        if ($existing) {
            $this->addFlash('error', 'Cette catégorie a déjà un bloc configuré.');
            return $this->redirectToRoute('manage_home_category_blocks_setting');
        }

        $block = new HomeCategoryBlockSetting();
        $block->setCategory($category);
        $block->setPosition($repository->findNextPosition());
        $em->persist($block);

        // Les 4 onglets de tri sont créés automatiquement, dans l'ordre par défaut, 4 produits chacun.
        $position = 0;
        foreach (array_keys(HomeCategoryBlockSortTab::SORT_KEYS) as $sortKey) {
            $tab = new HomeCategoryBlockSortTab();
            $tab->setBlock($block);
            $tab->setSortKey($sortKey);
            $tab->setPosition($position++);
            $tab->setProductCount(4);
            $em->persist($tab);
        }

        $em->flush();

        $this->addFlash('success', 'Bloc ajouté.');
        return $this->redirectToRoute('manage_home_category_blocks_setting');
    }

    #[Route('/parametres/blocs-categorie-accueil/{id}/supprimer', name: 'manage_home_category_blocks_remove', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function removeBlock(HomeCategoryBlockSetting $block, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($block);
        $em->flush();

        $this->addFlash('success', 'Bloc retiré.');
        return $this->redirectToRoute('manage_home_category_blocks_setting');
    }

    #[Route('/parametres/blocs-categorie-accueil/{id}/deplacer/{direction}', name: 'manage_home_category_blocks_move', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function moveBlock(HomeCategoryBlockSetting $block, string $direction, HomeCategoryBlockSettingRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $blocks = $repository->findAllOrdered();
        $index = array_search($block->getId(), array_map(fn ($b) => $b->getId(), $blocks), true);
        $swapWith = 'up' === $direction ? $index - 1 : $index + 1;

        if ($swapWith >= 0 && $swapWith < count($blocks)) {
            $a = $blocks[$index]->getPosition();
            $b = $blocks[$swapWith]->getPosition();
            $blocks[$index]->setPosition($b);
            $blocks[$swapWith]->setPosition($a);
            $em->flush();
        }

        return $this->redirectToRoute('manage_home_category_blocks_setting');
    }

    #[Route('/parametres/blocs-categorie-accueil/{id}/sous-categories/ajouter', name: 'manage_home_category_blocks_add_subcategory', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addSubcategory(HomeCategoryBlockSetting $block, Request $request, HomeCategoryBlockSubcategoryRepository $subcategoryRepository, EntityManagerInterface $em): RedirectResponse
    {
        $categoryId = (int) $request->request->get('category_id');
        $category = $em->getRepository(Category::class)->find($categoryId);

        if ($category) {
            $alreadyThere = false;
            foreach ($block->getSubcategoryItems() as $item) {
                if ($item->getCategory()->getId() === $category->getId()) {
                    $alreadyThere = true;
                    break;
                }
            }

            if (!$alreadyThere) {
                $item = new HomeCategoryBlockSubcategory();
                $item->setBlock($block);
                $item->setCategory($category);
                $item->setPosition($subcategoryRepository->findNextPosition($block));
                $em->persist($item);
                $em->flush();
            }
        }

        return $this->redirectToRoute('manage_home_category_blocks_setting');
    }

    #[Route('/parametres/blocs-categorie-accueil/sous-categorie/{id}/supprimer', name: 'manage_home_category_blocks_remove_subcategory', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function removeSubcategory(HomeCategoryBlockSubcategory $item, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($item);
        $em->flush();

        return $this->redirectToRoute('manage_home_category_blocks_setting');
    }

    #[Route('/parametres/blocs-categorie-accueil/sous-categorie/{id}/deplacer/{direction}', name: 'manage_home_category_blocks_move_subcategory', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function moveSubcategory(HomeCategoryBlockSubcategory $item, string $direction, HomeCategoryBlockSubcategoryRepository $subcategoryRepository, EntityManagerInterface $em): RedirectResponse
    {
        $items = $subcategoryRepository->findByBlockOrdered($item->getBlock());
        $index = array_search($item->getId(), array_map(fn ($i) => $i->getId(), $items), true);
        $swapWith = 'up' === $direction ? $index - 1 : $index + 1;

        if ($swapWith >= 0 && $swapWith < count($items)) {
            $a = $items[$index]->getPosition();
            $b = $items[$swapWith]->getPosition();
            $items[$index]->setPosition($b);
            $items[$swapWith]->setPosition($a);
            $em->flush();
        }

        return $this->redirectToRoute('manage_home_category_blocks_setting');
    }

    #[Route('/parametres/blocs-categorie-accueil/onglet/{id}/deplacer/{direction}', name: 'manage_home_category_blocks_move_sort_tab', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function moveSortTab(HomeCategoryBlockSortTab $tab, string $direction, HomeCategoryBlockSortTabRepository $tabRepository, EntityManagerInterface $em): RedirectResponse
    {
        $tabs = $tabRepository->findByBlockOrdered($tab->getBlock());
        $index = array_search($tab->getId(), array_map(fn ($t) => $t->getId(), $tabs), true);
        $swapWith = 'up' === $direction ? $index - 1 : $index + 1;

        if ($swapWith >= 0 && $swapWith < count($tabs)) {
            $a = $tabs[$index]->getPosition();
            $b = $tabs[$swapWith]->getPosition();
            $tabs[$index]->setPosition($b);
            $tabs[$swapWith]->setPosition($a);
            $em->flush();
        }

        return $this->redirectToRoute('manage_home_category_blocks_setting');
    }

    #[Route('/parametres/blocs-categorie-accueil/onglet/{id}/basculer-visibilite', name: 'manage_home_category_blocks_toggle_sort_tab', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleSortTabVisibility(HomeCategoryBlockSortTab $tab, EntityManagerInterface $em): Response
    {
        $tab->setVisible(!$tab->isVisible());
        $em->flush();

        return $this->json(['ok' => true, 'visible' => $tab->isVisible()]);
    }

    #[Route('/parametres/blocs-categorie-accueil/onglet/{id}/enregistrer', name: 'manage_home_category_blocks_update_sort_tab', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateSortTab(HomeCategoryBlockSortTab $tab, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $tab->setProductCount(max(1, (int) $request->request->get('product_count', 4)));
        $em->flush();

        $this->addFlash('success', 'Nombre de produits mis à jour.');
        return $this->redirectToRoute('manage_home_category_blocks_setting');
    }

    #[Route('/parametres/blocs-categorie-accueil/{id}/generer-onglets', name: 'manage_home_category_blocks_generate_sort_tabs', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function generateDefaultSortTabs(HomeCategoryBlockSetting $block, EntityManagerInterface $em): RedirectResponse
    {
        if (count($block->getSortTabs()) === 0) {
            $position = 0;
            foreach (array_keys(HomeCategoryBlockSortTab::SORT_KEYS) as $sortKey) {
                $tab = new HomeCategoryBlockSortTab();
                $tab->setBlock($block);
                $tab->setSortKey($sortKey);
                $tab->setPosition($position++);
                $tab->setProductCount(4);
                $em->persist($tab);
            }
            $em->flush();
            $this->addFlash('success', 'Onglets de tri générés.');
        }

        return $this->redirectToRoute('manage_home_category_blocks_setting');
    }

    /** Cascade générique — enfants d'une catégorie (réutilisé pour choisir le bloc ET ses sous-catégories, n'importe quel niveau). */
    #[Route('/parametres/blocs-categorie-accueil/categories-enfants', name: 'manage_home_category_blocks_children', host: 'manage.kongobazar.com', methods: ['GET'])]
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
