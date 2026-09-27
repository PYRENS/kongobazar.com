<?php

namespace App\Controller\Manage;

use App\Repository\RecentlyViewedSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RecentlyViewedSettingController extends AbstractController
{
    #[Route('/parametres/consulte-recemment-accueil', name: 'manage_recently_viewed_setting', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(RecentlyViewedSettingRepository $repository): Response
    {
        return $this->render('manage/recently_viewed_setting/index.html.twig', [
            'setting' => $repository->getSingleton(),
        ]);
    }

    #[Route('/parametres/consulte-recemment-accueil/basculer', name: 'manage_recently_viewed_toggle_enabled', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function toggleEnabled(RecentlyViewedSettingRepository $repository, EntityManagerInterface $em): Response
    {
        $setting = $repository->getSingleton();
        $setting->setEnabled(!$setting->isEnabled());
        $em->flush();

        return $this->json(['ok' => true, 'enabled' => $setting->isEnabled()]);
    }

    #[Route('/parametres/consulte-recemment-accueil', name: 'manage_recently_viewed_setting_update', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function update(Request $request, RecentlyViewedSettingRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $setting = $repository->getSingleton();
        $setting->setDisplayCount(max(6, (int) $request->request->get('display_count', 20)));
        $em->flush();

        $this->addFlash('success', 'Réglages enregistrés.');
        return $this->redirectToRoute('manage_recently_viewed_setting');
    }
}