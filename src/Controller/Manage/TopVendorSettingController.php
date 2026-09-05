<?php

namespace App\Controller\Manage;

use App\Entity\SellerProfile;
use App\Entity\TopVendorSetting;
use App\Entity\TopVendorTargetedSeller;
use App\Repository\SellerProfileRepository;
use App\Repository\TopVendorSettingRepository;
use App\Repository\TopVendorTargetedSellerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TopVendorSettingController extends AbstractController
{
    public const MODES = [
        'auto' => 'Affichage automatique (meilleurs vendeurs)',
        'targeted' => 'Affichage ciblé',
    ];

    #[Route('/parametres/top-vendeur-accueil', name: 'manage_top_vendor_setting', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(TopVendorSettingRepository $repository, TopVendorTargetedSellerRepository $targetedRepository): Response
    {
        $setting = $repository->getSingleton();

        return $this->render('manage/top_vendor_setting/index.html.twig', [
            'setting' => $setting,
            'modes' => self::MODES,
            'targetedSellers' => $targetedRepository->findBySettingOrdered($setting),
        ]);
    }

    #[Route('/parametres/top-vendeur-accueil/basculer', name: 'manage_top_vendor_toggle_enabled', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function toggleEnabled(TopVendorSettingRepository $repository, EntityManagerInterface $em): Response
    {
        $setting = $repository->getSingleton();
        $setting->setEnabled(!$setting->isEnabled());
        $em->flush();

        return $this->json(['ok' => true, 'enabled' => $setting->isEnabled()]);
    }

    #[Route('/parametres/top-vendeur-accueil', name: 'manage_top_vendor_setting_update', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function update(Request $request, TopVendorSettingRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $setting = $repository->getSingleton();

        $setting->setDisplayMode($request->request->get('display_mode', 'auto'));
        $setting->setDisplayCount(max(1, (int) $request->request->get('display_count', 4)));
        $setting->setExcludePro((bool) $request->request->get('exclude_pro'));
        $setting->setExcludeBoutique((bool) $request->request->get('exclude_boutique'));

        $em->flush();

        $this->addFlash('success', 'Réglages "Top Vendeur" enregistrés.');
        return $this->redirectToRoute('manage_top_vendor_setting');
    }

    #[Route('/parametres/top-vendeur-accueil/vendeur/ajouter', name: 'manage_top_vendor_add_seller', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function addTargetedSeller(Request $request, TopVendorSettingRepository $repository, TopVendorTargetedSellerRepository $targetedRepository, EntityManagerInterface $em): RedirectResponse
    {
        $setting = $repository->getSingleton();
        $sellerId = (int) $request->request->get('seller_id');
        $seller = $em->getRepository(SellerProfile::class)->find($sellerId);

        if ($seller) {
            $alreadyThere = false;
            foreach ($setting->getTargetedSellers() as $item) {
                if ($item->getSeller()->getId() === $seller->getId()) {
                    $alreadyThere = true;
                    break;
                }
            }
            if (!$alreadyThere) {
                $item = new TopVendorTargetedSeller();
                $item->setSetting($setting);
                $item->setSeller($seller);
                $item->setPosition($targetedRepository->findNextPosition($setting));
                $em->persist($item);
                $em->flush();
            }
        }

        return $this->redirectToRoute('manage_top_vendor_setting');
    }

    #[Route('/parametres/top-vendeur-accueil/vendeur/{id}/supprimer', name: 'manage_top_vendor_remove_seller', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function removeTargetedSeller(TopVendorTargetedSeller $item, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($item);
        $em->flush();

        return $this->redirectToRoute('manage_top_vendor_setting');
    }

    #[Route('/parametres/top-vendeur-accueil/vendeur/{id}/deplacer/{direction}', name: 'manage_top_vendor_move_seller', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function moveTargetedSeller(TopVendorTargetedSeller $item, string $direction, TopVendorTargetedSellerRepository $targetedRepository, EntityManagerInterface $em): RedirectResponse
    {
        $items = $targetedRepository->findBySettingOrdered($item->getSetting());
        $index = array_search($item->getId(), array_map(fn ($i) => $i->getId(), $items), true);
        $swapWith = 'up' === $direction ? $index - 1 : $index + 1;

        if ($swapWith >= 0 && $swapWith < count($items)) {
            $a = $items[$index]->getPosition();
            $b = $items[$swapWith]->getPosition();
            $items[$index]->setPosition($b);
            $items[$swapWith]->setPosition($a);
            $em->flush();
        }

        return $this->redirectToRoute('manage_top_vendor_setting');
    }

    #[Route('/parametres/top-vendeur-accueil/rechercher-vendeurs', name: 'manage_top_vendor_search_sellers', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function searchSellers(Request $request, SellerProfileRepository $repository): Response
    {
        $term = trim((string) $request->query->get('q', ''));
        $results = mb_strlen($term) >= 1 ? $repository->searchByName($term) : [];

        return $this->json(['results' => array_map(fn (SellerProfile $s) => [
            'id' => $s->getId(),
            'name' => $s->getDisplayName(),
        ], $results)]);
    }
}
