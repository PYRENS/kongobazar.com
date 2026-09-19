<?php

namespace App\Controller\Manage;

use App\Entity\Product;
use App\Repository\HotDealSectionSettingRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HotDealSectionSettingController extends AbstractController
{
    #[Route('/parametres/offre-exceptionnelle-accueil', name: 'manage_hot_deal_setting', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(HotDealSectionSettingRepository $repository): Response
    {
        return $this->render('manage/hot_deal_setting/index.html.twig', [
            'setting' => $repository->getSingleton(),
        ]);
    }

    #[Route('/parametres/offre-exceptionnelle-accueil/basculer', name: 'manage_hot_deal_toggle_enabled', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function toggleEnabled(HotDealSectionSettingRepository $repository, EntityManagerInterface $em): Response
    {
        $setting = $repository->getSingleton();
        $setting->setEnabled(!$setting->isEnabled());
        $em->flush();

        return $this->json(['ok' => true, 'enabled' => $setting->isEnabled()]);
    }

    #[Route('/parametres/offre-exceptionnelle-accueil', name: 'manage_hot_deal_setting_update', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function update(Request $request, HotDealSectionSettingRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $setting = $repository->getSingleton();
        $setting->setDisplayCount((int) $request->request->get('display_count', 5));
        $em->flush();

        $this->addFlash('success', 'Réglages enregistrés.');
        return $this->redirectToRoute('manage_hot_deal_setting');
    }

    #[Route('/parametres/offre-exceptionnelle-accueil/produit/ajouter', name: 'manage_hot_deal_add_product', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function addProduct(Request $request, HotDealSectionSettingRepository $repository, ProductRepository $productRepository, EntityManagerInterface $em): Response
    {
        $setting = $repository->getSingleton();
        $product = $productRepository->find((int) $request->request->get('product_id'));

        $activeIds = array_map(fn (Product $p) => $p->getId(), $productRepository->findAllActiveDeals());
        if (!$product || !in_array($product->getId(), $activeIds, true)) {
            return $this->json(['ok' => false, 'error' => 'Produit introuvable ou pas en vente flash active.']);
        }

        $setting->addTargetedProduct($product);
        $em->flush();

        return $this->json([
            'ok' => true,
            'productId' => $product->getId(),
            'productTitle' => $product->getTitle(),
            'reference' => $product->getKongobazarReference(),
            'imageUrl' => $product->getImages()->count() > 0 ? '/media/products/' . $product->getImages()->first()->getImageName() : null,
        ]);
    }

    #[Route('/parametres/offre-exceptionnelle-accueil/produit/{id}/retirer', name: 'manage_hot_deal_remove_product', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function removeProduct(Product $product, HotDealSectionSettingRepository $repository, EntityManagerInterface $em): Response
    {
        $setting = $repository->getSingleton();
        $setting->removeTargetedProduct($product);
        $em->flush();

        return $this->json(['ok' => true]);
    }

    #[Route('/parametres/offre-exceptionnelle-accueil/rechercher-produits', name: 'manage_hot_deal_search_products', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function searchProducts(Request $request, ProductRepository $productRepository): Response
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
            'title' => $p->getTitle(),
            'reference' => $p->getKongobazarReference(),
            'imageUrl' => $p->getImages()->count() > 0 ? '/media/products/' . $p->getImages()->first()->getImageName() : null,
        ], $deals)]);
    }
}
