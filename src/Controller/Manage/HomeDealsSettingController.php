<?php

namespace App\Controller\Manage;

use App\Entity\Category;
use App\Entity\HomeDealsSetting;
use App\Entity\Product;
use App\Entity\SellerProfile;
use App\Repository\HomeDealsSettingRepository;
use App\Repository\ProductRepository;
use App\Repository\SellerProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeDealsSettingController extends AbstractController
{
    public const MODES = [
        'random' => 'Affichage aléatoire',
        'kbz_only' => 'Affichage boutique KBZ',
        'mixed' => 'Affichage mixte',
        'targeted_stores' => 'Affichage ciblé store',
        'targeted_products' => 'Affichage ciblé produit',
        'category' => 'Affichage catégorie',
    ];

    #[Route('/parametres/ventes-flash-accueil', name: 'manage_home_deals_setting', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(HomeDealsSettingRepository $repository, ProductRepository $productRepository, \App\Repository\CategoryRepository $categoryRepository): Response
    {
        $setting = $repository->getSingleton();

        return $this->render('manage/home_deals_setting/index.html.twig', [
            'setting' => $setting,
            'modes' => self::MODES,
            'activeDealsCount' => count($productRepository->findAllActiveDeals()),
            'rootCategories' => $categoryRepository->findChildrenOf(null),
        ]);
    }

    #[Route('/parametres/ventes-flash-accueil/basculer', name: 'manage_home_deals_toggle_enabled', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function toggleEnabled(HomeDealsSettingRepository $repository, EntityManagerInterface $em): Response
    {
        $setting = $repository->getSingleton();
        $setting->setEnabled(!$setting->isEnabled());
        $em->flush();

        return $this->json(['ok' => true, 'enabled' => $setting->isEnabled()]);
    }

    #[Route('/parametres/ventes-flash-accueil', name: 'manage_home_deals_setting_update', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function update(Request $request, HomeDealsSettingRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $setting = $repository->getSingleton();

        $setting->setDisplayCount(max(1, (int) $request->request->get('display_count', 10)));
        $setting->setDisplayMode($request->request->get('display_mode', 'random'));
        $setting->setExcludeBoutique((bool) $request->request->get('exclude_boutique'));
        $setting->setExcludePro((bool) $request->request->get('exclude_pro'));

        $mixedKbz = $request->request->get('mixed_kbz_count');
        $mixedOther = $request->request->get('mixed_other_count');
        $setting->setMixedKbzCount('' !== $mixedKbz ? (int) $mixedKbz : null);
        $setting->setMixedOtherCount('' !== $mixedOther ? (int) $mixedOther : null);

        $setting->clearExcludedSellers();
        foreach ($request->request->all('excluded_seller_ids') as $id) {
            $seller = $em->getRepository(SellerProfile::class)->find((int) $id);
            if ($seller) {
                $setting->addExcludedSeller($seller);
            }
        }

        $setting->clearTargetedSellers();
        foreach ($request->request->all('targeted_seller_ids') as $id) {
            $seller = $em->getRepository(SellerProfile::class)->find((int) $id);
            if ($seller) {
                $setting->addTargetedSeller($seller);
            }
        }

        $setting->clearTargetedProducts();
        foreach ($request->request->all('targeted_product_ids') as $id) {
            $product = $em->getRepository(Product::class)->find((int) $id);
            if ($product) {
                $setting->addTargetedProduct($product);
            }
        }

        $setting->clearTargetedCategories();
        foreach ($request->request->all('targeted_category_ids') as $id) {
            $category = $em->getRepository(Category::class)->find((int) $id);
            if ($category) {
                $setting->addTargetedCategory($category);
            }
        }

        $em->flush();

        $this->addFlash('success', 'Réglages "Ventes flash accueil" enregistrés.');
        return $this->redirectToRoute('manage_home_deals_setting');
    }

    #[Route('/parametres/ventes-flash-accueil/rechercher-vendeurs', name: 'manage_home_deals_search_sellers', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function searchSellers(Request $request, SellerProfileRepository $repository): Response
    {
        $term = trim((string) $request->query->get('q', ''));
        $results = mb_strlen($term) >= 1 ? $repository->searchByName($term) : [];

        return $this->json(['results' => array_map(fn (SellerProfile $s) => [
            'id' => $s->getId(),
            'name' => $s->getDisplayName() . ($s->isKbz() ? ' (KBZ)' : ''),
        ], $results)]);
    }

    #[Route('/parametres/ventes-flash-accueil/rechercher-produits-actifs', name: 'manage_home_deals_search_products', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function searchActiveDeals(Request $request, ProductRepository $productRepository): Response
    {
        $term = mb_strtolower(trim((string) $request->query->get('q', '')));
        $deals = $productRepository->findAllActiveDeals();

        if ($term) {
            $deals = array_filter($deals, fn (Product $p) =>
                str_contains(mb_strtolower($p->getTitle()), $term)
                || str_contains(mb_strtolower((string) $p->getKongobazarReference()), $term)
            );
        }

        $deals = array_slice($deals, 0, 20);

        return $this->json(['results' => array_map(fn (Product $p) => [
            'id' => $p->getId(),
            'name' => $p->getTitle() . ' (' . $p->getKongobazarReference() . ')',
        ], $deals)]);
    }
}
