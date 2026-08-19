<?php

namespace App\Controller\Manage;

use App\Repository\HeroSideAdsSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HeroSideAdsSettingController extends AbstractController
{
    #[Route('/hero-pubs-laterales', name: 'manage_hero_side_ads_setting', host: 'manage.kongobazar.com', methods: ['GET', 'POST'])]
    public function index(Request $request, HeroSideAdsSettingRepository $repository, EntityManagerInterface $em): Response
    {
        $setting = $repository->getSingleton();

        if ($request->isMethod('POST')) {
            $value = $request->request->get('hide_below_width');
            $setting->setHideBelowWidth($value !== '' ? (int) $value : null);
            $em->flush();

            $this->addFlash('success', 'Réglage enregistré.');
            return $this->redirectToRoute('manage_hero_side_ads_setting');
        }

        return $this->render('manage/hero_side_ads/index.html.twig', [
            'setting' => $setting,
        ]);
    }
}