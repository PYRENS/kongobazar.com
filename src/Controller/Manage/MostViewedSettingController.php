<?php

namespace App\Controller\Manage;

use App\Repository\MostViewedSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MostViewedSettingController extends AbstractController
{
    #[Route('/parametres/plus-consultes-accueil', name: 'manage_most_viewed_setting', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(MostViewedSettingRepository $repository): Response
    {
        return $this->render('manage/most_viewed_setting/index.html.twig', [
            'setting' => $repository->getSingleton(),
        ]);
    }

    #[Route('/parametres/plus-consultes-accueil/basculer', name: 'manage_most_viewed_toggle_enabled', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function toggleEnabled(MostViewedSettingRepository $repository, EntityManagerInterface $em): Response
    {
        $setting = $repository->getSingleton();
        $setting->setEnabled(!$setting->isEnabled());
        $em->flush();

        return $this->json(['ok' => true, 'enabled' => $setting->isEnabled()]);
    }

    #[Route('/parametres/plus-consultes-accueil', name: 'manage_most_viewed_setting_update', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function update(Request $request, MostViewedSettingRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $setting = $repository->getSingleton();
        $setting->setDisplayCount(max(6, (int) $request->request->get('display_count', 20)));
        $setting->setIncludeKbz((bool) $request->request->get('include_kbz'));
        $setting->setIncludeStore((bool) $request->request->get('include_store'));
        $setting->setIncludePro((bool) $request->request->get('include_pro'));
        $setting->setIncludeIndividual((bool) $request->request->get('include_individual'));

        $em->flush();

        $this->addFlash('success', 'Réglages enregistrés.');
        return $this->redirectToRoute('manage_most_viewed_setting');
    }
}
