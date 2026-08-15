<?php

namespace App\Controller\Manage;

use App\Entity\AdZoneSetting;
use App\Entity\Advertisement;
use App\Repository\AdvertisementRepository;
use App\Repository\AdZoneSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdZoneSettingController extends AbstractController
{
    /** Zones à emplacement unique — seules concernées par le réglage Aléatoire/Fixe (les zones carrousel/mosaïque affichent déjà tout en simultané), classées par page pour le filtre admin. */
    private const SINGLE_SLOT_ZONES = [
        'homepage_hero_side_top' => 'homepage',
        'homepage_hero_side_bottom' => 'homepage',
        'sidebar_top' => 'homepage',
        'sidebar_middle' => 'homepage',
        'homepage_promo_strip' => 'homepage',
        'homepage_center_banner' => 'homepage',
        'homepage_lifestyle_left' => 'homepage',
        'homepage_lifestyle_center' => 'homepage',
        'homepage_lifestyle_right' => 'homepage',
        'footer_social_banner' => 'footer',
        'footer_callus_photo' => 'footer',
    ];

    private const PAGE_GROUPS = [
        'homepage' => 'Page d\'accueil',
        'footer' => 'Pied de page',
    ];

    #[Route('/publicites/zones', name: 'manage_ad_zones_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(AdZoneSettingRepository $settingRepository, AdvertisementRepository $adRepository): Response
    {
        $rows = [];
        foreach (self::SINGLE_SLOT_ZONES as $zoneKey => $pageGroup) {
            $setting = $settingRepository->findOneByZoneKey($zoneKey);
            $rows[] = [
                'zoneKey' => $zoneKey,
                'pageGroup' => $pageGroup,
                'width' => AdvertisementManagementController::ZONE_INFO[$zoneKey]['width'] ?? null,
                'height' => AdvertisementManagementController::ZONE_INFO[$zoneKey]['height'] ?? null,
                'mode' => $setting ? $setting->getMode() : 'random',
                'fixedAdvertisementId' => $setting && $setting->getFixedAdvertisement() ? $setting->getFixedAdvertisement()->getId() : null,
                'candidates' => $adRepository->findActiveByZone($zoneKey, 'public'),
            ];
        }

        return $this->render('manage/advertisements/zones.html.twig', [
            'rows' => $rows,
            'pageGroups' => self::PAGE_GROUPS,
        ]);
    }

    #[Route('/publicites/zones/enregistrer', name: 'manage_ad_zones_save', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function save(Request $request, AdZoneSettingRepository $settingRepository, EntityManagerInterface $em): RedirectResponse
    {
        foreach (self::SINGLE_SLOT_ZONES as $zoneKey => $pageGroup) {
            $mode = $request->request->get('mode_' . $zoneKey, 'random');
            $fixedId = $request->request->get('fixed_' . $zoneKey);

            $setting = $settingRepository->findOneByZoneKey($zoneKey) ?? new AdZoneSetting();
            $setting->setZoneKey($zoneKey);
            $setting->setMode($mode);
            $setting->setFixedAdvertisement($fixedId ? $em->getRepository(Advertisement::class)->find((int) $fixedId) : null);

            $em->persist($setting);
        }

        $em->flush();

        $this->addFlash('success', 'Réglages des zones mis à jour.');
        return $this->redirectToRoute('manage_ad_zones_index');
    }
}
