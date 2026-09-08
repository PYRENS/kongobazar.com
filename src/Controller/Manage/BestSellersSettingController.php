<?php

namespace App\Controller\Manage;

use App\Repository\BestSellersSectionSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BestSellersSettingController extends AbstractController
{
    #[Route('/parametres/meilleures-ventes-accueil', name: 'manage_best_sellers_setting', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(BestSellersSectionSettingRepository $repository): Response
    {
        return $this->render('manage/best_sellers_setting/index.html.twig', [
            'setting' => $repository->getSingleton(),
        ]);
    }

    #[Route('/parametres/meilleures-ventes-accueil/basculer', name: 'manage_best_sellers_toggle_enabled', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function toggleEnabled(BestSellersSectionSettingRepository $repository, EntityManagerInterface $em): Response
    {
        $setting = $repository->getSingleton();
        $setting->setEnabled(!$setting->isEnabled());
        $em->flush();

        return $this->json(['ok' => true, 'enabled' => $setting->isEnabled()]);
    }

    #[Route('/parametres/meilleures-ventes-accueil', name: 'manage_best_sellers_setting_update', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function update(Request $request, BestSellersSectionSettingRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $setting = $repository->getSingleton();
        $setting->setDisplayCount(max(3, (int) $request->request->get('display_count', 8)));

        $em->flush();

        $this->addFlash('success', 'Réglages enregistrés.');
        return $this->redirectToRoute('manage_best_sellers_setting');
    }
}
