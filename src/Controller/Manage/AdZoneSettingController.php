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
    public function index(Request $request, AdZoneSettingRepository $settingRepository, AdvertisementRepository $adRepository): Response
    {
        $built = $this->buildRows($request, $settingRepository, $adRepository);

        return $this->render('manage/advertisements/zones.html.twig', [
            'stats' => $built['stats'],
            'rows' => $built['rows'],
            'pageGroups' => self::PAGE_GROUPS,
            'currentPageGroup' => $built['pageGroup'],
            'currentMode' => $built['mode'],
            'page' => $built['page'],
            'pages' => $built['pages'],
            'perPage' => $built['perPage'],
            'total' => $built['total'],
        ]);
    }

    #[Route('/publicites/zones/liste-fragment', name: 'manage_ad_zones_index_fragment', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function indexFragment(Request $request, AdZoneSettingRepository $settingRepository, AdvertisementRepository $adRepository): Response
    {
        $built = $this->buildRows($request, $settingRepository, $adRepository);

        return $this->json([
            'rowsHtml' => $this->renderView('manage/advertisements/_zones_rows.html.twig', [
                'rows' => $built['rows'],
                'pageGroups' => self::PAGE_GROUPS,
            ]),
            'footerInfo' => $built['total'] . ' zone' . ($built['total'] != 1 ? 's' : '') . ' au total — page ' . $built['page'] . ' / ' . $built['pages'],
            'paginationHtml' => $this->renderView('manage/advertisements/_index_pagination.html.twig', ['page' => $built['page'], 'pages' => $built['pages']]),
        ]);
    }

    private function buildRows(Request $request, AdZoneSettingRepository $settingRepository, AdvertisementRepository $adRepository): array
    {
        $pageGroup = $request->query->get('pageGroup') ?: null;
        $mode = $request->query->get('mode') ?: null;

        $rows = [];
        foreach (self::SINGLE_SLOT_ZONES as $zoneKey => $group) {
            if ($pageGroup && $pageGroup !== $group) {
                continue;
            }

            $setting = $settingRepository->findOneByZoneKey($zoneKey);
            $rowMode = $setting ? $setting->getMode() : 'random';

            if ($mode && $mode !== $rowMode) {
                continue;
            }

            $candidates = $adRepository->findActiveByZone($zoneKey, 'public');

            $rows[] = [
                'zoneKey' => $zoneKey,
                'pageGroup' => $group,
                'width' => AdvertisementManagementController::ZONE_INFO[$zoneKey]['width'] ?? null,
                'height' => AdvertisementManagementController::ZONE_INFO[$zoneKey]['height'] ?? null,
                'mode' => $rowMode,
                'fixedAdvertisementId' => $setting && $setting->getFixedAdvertisement() ? $setting->getFixedAdvertisement()->getId() : null,
                'candidates' => $candidates,
                'totalImpressions' => array_sum(array_map(fn ($ad) => $ad->getImpressionCount(), $candidates)),
                'totalClicks' => array_sum(array_map(fn ($ad) => $ad->getClickCount(), $candidates)),
            ];
        }

        $stats = [
            'totalZones' => count(self::SINGLE_SLOT_ZONES),
            'withActiveBanner' => count(array_filter($rows, fn ($r) => count($r['candidates']) > 0)),
            'fixedMode' => count(array_filter($rows, fn ($r) => 'fixed' === $r['mode'])),
            'totalImpressions' => array_sum(array_column($rows, 'totalImpressions')),
        ];

        $perPage = in_array((int) $request->query->get('perPage', 20), [10, 20, 50, 100], true)
            ? (int) $request->query->get('perPage', 20) : 20;
        $page = max(1, (int) $request->query->get('page', 1));
        $total = count($rows);
        $rows = array_slice($rows, ($page - 1) * $perPage, $perPage);

        return [
            'rows' => $rows,
            'stats' => $stats,
            'pageGroup' => $pageGroup,
            'mode' => $mode,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'perPage' => $perPage,
            'total' => $total,
        ];
    }

    /** Enregistre le réglage d'UNE seule zone (AJAX) — évite d'écraser les autres zones non affichées sur la page courante. */
    #[Route('/publicites/zones/{zoneKey}/enregistrer', name: 'manage_ad_zones_save_one', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function saveOne(string $zoneKey, Request $request, AdZoneSettingRepository $settingRepository, EntityManagerInterface $em): Response
    {
        if (!isset(self::SINGLE_SLOT_ZONES[$zoneKey])) {
            throw $this->createNotFoundException('Zone inconnue.');
        }

        $mode = $request->request->get('mode', 'random');
        $fixedId = $request->request->get('fixed_advertisement_id');

        $setting = $settingRepository->findOneByZoneKey($zoneKey) ?? new AdZoneSetting();
        $setting->setZoneKey($zoneKey);
        $setting->setMode($mode);
        $setting->setFixedAdvertisement($fixedId ? $em->getRepository(Advertisement::class)->find((int) $fixedId) : null);

        $em->persist($setting);
        $em->flush();

        return $this->json(['ok' => true]);
    }
}
