<?php

namespace App\Controller\Manage;

use App\Entity\Product;
use App\Entity\TrendingTabSetting;
use App\Repository\CategoryRepository;
use App\Repository\TrendingSectionSettingRepository;
use App\Repository\TrendingTabSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TrendingTabSettingController extends AbstractController
{
    public const MODES = [
        'recent' => 'Plus récents',
        'best_sellers' => 'Meilleures ventes',
        'random' => 'Aléatoire',
        'targeted' => 'Ciblé produit',
    ];

    #[Route('/parametres/articles-tendances-accueil', name: 'manage_home_trending_tabs_setting', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(TrendingTabSettingRepository $repository, TrendingSectionSettingRepository $sectionRepository, CategoryRepository $categoryRepository): Response
    {
        $tabs = $repository->findAllOrdered();
        $usedCategoryIds = array_map(fn ($tab) => $tab->getCategory()->getId(), $tabs);

        return $this->render('manage/trending_tabs_setting/index.html.twig', [
            'tabs' => $tabs,
            'sectionSetting' => $sectionRepository->getSingleton(),
            'modes' => self::MODES,
            'rootCategories' => $categoryRepository->findChildrenOf(null),
            'usedCategoryIds' => $usedCategoryIds,
        ]);
    }

    #[Route('/parametres/articles-tendances-accueil/basculer', name: 'manage_home_trending_tabs_toggle_enabled', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function toggleEnabled(TrendingSectionSettingRepository $repository, EntityManagerInterface $em): Response
    {
        $setting = $repository->getSingleton();
        $setting->setEnabled(!$setting->isEnabled());
        $em->flush();

        return $this->json(['ok' => true, 'enabled' => $setting->isEnabled()]);
    }

    #[Route('/parametres/articles-tendances-accueil/onglet/ajouter', name: 'manage_home_trending_tabs_add', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function addTab(Request $request, TrendingTabSettingRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $categoryId = (int) $request->request->get('category_id');
        $category = $em->getRepository(\App\Entity\Category::class)->find($categoryId);

        if (!$category) {
            $this->addFlash('error', 'Catégorie introuvable.');
            return $this->redirectToRoute('manage_home_trending_tabs_setting');
        }

        $existing = $em->getRepository(TrendingTabSetting::class)->findOneBy(['category' => $category]);
        if ($existing) {
            $this->addFlash('error', 'Cette catégorie a déjà un onglet configuré — impossible de la sélectionner deux fois.');
            return $this->redirectToRoute('manage_home_trending_tabs_setting');
        }

        $tab = new TrendingTabSetting();
        $tab->setCategory($category);
        $tab->setPosition($repository->findNextPosition());
        $em->persist($tab);
        $em->flush();
        $this->addFlash('success', 'Onglet ajouté.');

        return $this->redirectToRoute('manage_home_trending_tabs_setting');
    }

    #[Route('/parametres/articles-tendances-accueil/onglet/{id}/supprimer', name: 'manage_home_trending_tabs_remove', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function removeTab(TrendingTabSetting $tab, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($tab);
        $em->flush();

        $this->addFlash('success', 'Onglet retiré.');
        return $this->redirectToRoute('manage_home_trending_tabs_setting');
    }

    #[Route('/parametres/articles-tendances-accueil/onglet/{id}/deplacer/{direction}', name: 'manage_home_trending_tabs_move', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function moveTab(TrendingTabSetting $tab, string $direction, TrendingTabSettingRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $tabs = $repository->findAllOrdered();
        $index = array_search($tab->getId(), array_map(fn ($t) => $t->getId(), $tabs), true);
        $swapWith = 'up' === $direction ? $index - 1 : $index + 1;

        if ($swapWith >= 0 && $swapWith < count($tabs)) {
            $a = $tabs[$index]->getPosition();
            $b = $tabs[$swapWith]->getPosition();
            $tabs[$index]->setPosition($b);
            $tabs[$swapWith]->setPosition($a);
            $em->flush();
        }

        return $this->redirectToRoute('manage_home_trending_tabs_setting');
    }

    #[Route('/parametres/articles-tendances-accueil/onglet/{id}/enregistrer', name: 'manage_home_trending_tabs_update', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateTab(TrendingTabSetting $tab, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $tab->setMode($request->request->get('mode', 'recent'));
        $tab->setProductCount(max(1, (int) $request->request->get('product_count', 5)));

        $tab->clearTargetedProducts();
        foreach ($request->request->all('targeted_product_ids') as $id) {
            $product = $em->getRepository(Product::class)->find((int) $id);
            if ($product) {
                $tab->addTargetedProduct($product);
            }
        }

        $em->flush();

        $this->addFlash('success', 'Onglet mis à jour.');
        return $this->redirectToRoute('manage_home_trending_tabs_setting');
    }

    #[Route('/parametres/articles-tendances-accueil/rechercher-produits', name: 'manage_home_trending_tabs_search_products', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function searchProducts(Request $request, \App\Repository\ProductRepository $productRepository): Response
    {
        $term = trim((string) $request->query->get('q', ''));
        $categoryId = $request->query->get('category_id') ? (int) $request->query->get('category_id') : null;

        $qb = $productRepository->createQueryBuilder('p')
            ->andWhere('p.status = :status')->setParameter('status', 'active')
            ->setMaxResults(20);

        if ($term) {
            $qb->andWhere('p.title LIKE :term')->setParameter('term', '%' . $term . '%');
        }
        if ($categoryId) {
            $qb->andWhere('p.category = :categoryId')->setParameter('categoryId', $categoryId);
        }

        $results = $qb->getQuery()->getResult();

        return $this->json(['results' => array_map(fn (Product $p) => [
            'id' => $p->getId(),
            'name' => $p->getTitle() . ' (' . $p->getKongobazarReference() . ')',
        ], $results)]);
    }
}
