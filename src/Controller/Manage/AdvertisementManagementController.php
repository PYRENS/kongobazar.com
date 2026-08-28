<?php

namespace App\Controller\Manage;

use App\Entity\Advertisement;
use App\Entity\Category;
use App\Entity\SellerProfile;
use App\Repository\AdvertisementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdvertisementManagementController extends AbstractController
{
    /** Chaque zone : libellé + dimensions exactes attendues en pixels (null = pas encore de contrainte définie) + page où elle apparaît. */
    public const ZONE_INFO = [
        'homepage_hero_main' => ['label' => 'Hero — carrousel principal', 'width' => 754, 'height' => 420, 'page' => 'Accueil'],
        'homepage_hero_side_top' => ['label' => 'Hero — bannière latérale haute', 'width' => 270, 'height' => 200, 'page' => 'Accueil'],
        'homepage_hero_side_bottom' => ['label' => 'Hero — bannière latérale basse', 'width' => 270, 'height' => 200, 'page' => 'Accueil'],
        'sidebar_top' => ['label' => 'Colonne gauche — haut', 'width' => 270, 'height' => 200, 'page' => 'Accueil'],
        'sidebar_middle' => ['label' => 'Colonne gauche — milieu', 'width' => 270, 'height' => 200, 'page' => 'Accueil'],
        'homepage_promo_strip' => ['label' => 'Bandeau promo', 'width' => 1044, 'height' => 120, 'page' => 'Accueil'],
        'homepage_center_banner' => ['label' => 'Bannière centrale', 'width' => 1044, 'height' => 250, 'page' => 'Accueil'],
        'category_block_banner' => ['label' => 'Bannière bas de bloc catégorie (liée à une catégorie précise)', 'width' => null, 'height' => null, 'page' => 'Catégorie'],
        'homepage_lifestyle_left' => ['label' => 'Mosaïque lifestyle — gauche', 'width' => 255, 'height' => 220, 'page' => 'Accueil'],
        'homepage_lifestyle_center' => ['label' => 'Mosaïque lifestyle — centre', 'width' => 510, 'height' => 220, 'page' => 'Accueil'],
        'homepage_lifestyle_right' => ['label' => 'Mosaïque lifestyle — droite', 'width' => 255, 'height' => 220, 'page' => 'Accueil'],
        'footer_social_banner' => ['label' => 'Footer — bannière sociale', 'width' => null, 'height' => null, 'page' => 'Footer (toutes pages)'],
        'footer_mosaic' => ['label' => 'Footer — mosaïque photos', 'width' => null, 'height' => null, 'page' => 'Footer (toutes pages)'],
        'footer_callus_photo' => ['label' => 'Footer — photo "Appelez-nous"', 'width' => null, 'height' => null, 'page' => 'Footer (toutes pages)'],
        'mega_menu_catalogue_1' => ['label' => 'Méga-menu — bannière 1', 'width' => 320, 'height' => 180, 'page' => 'Méga-menu (toutes pages)'],
        'mega_menu_catalogue_2' => ['label' => 'Méga-menu — bannière 2', 'width' => 320, 'height' => 180, 'page' => 'Méga-menu (toutes pages)'],
        'mega_menu_catalogue_3' => ['label' => 'Méga-menu — bannière 3', 'width' => 320, 'height' => 180, 'page' => 'Méga-menu (toutes pages)'],
        'mega_menu_catalogue_4' => ['label' => 'Méga-menu — bannière 4', 'width' => 320, 'height' => 180, 'page' => 'Méga-menu (toutes pages)'],
        'rayon_flyout_ad_droite' => ['label' => 'Flyout rayon — position droite (liée à une catégorie précise)', 'width' => 220, 'height' => 400, 'page' => 'Catégorie'],
        'rayon_flyout_ad_bas' => ['label' => 'Flyout rayon — position bas (liée à une catégorie précise)', 'width' => 480, 'height' => 120, 'page' => 'Catégorie'],
        'mobile_top_rayons_offcanvas' => ['label' => 'Tiroir "Top Rayons" mobile', 'width' => 335, 'height' => 100, 'page' => 'Mobile (toutes pages)'],
    ];

    #[Route('/publicites', name: 'manage_ads_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(Request $request, AdvertisementRepository $repository): Response
    {
        $repository->expireOutdated();
        $built = $this->buildFilteredRows($request, $repository);

        return $this->render('manage/advertisements/index.html.twig', [
            'stats' => $built['stats'],
            'adRows' => $built['adRows'],
            'zoneKeys' => self::ZONE_INFO,
            'allSitePages' => $built['allSitePages'],
            'currentZone' => $built['zoneKey'],
            'currentStatus' => $built['status'],
            'currentSitePage' => $built['sitePage'],
            'searchTerm' => $built['term'],
            'currentSort' => $built['sort'],
            'currentDir' => $built['dir'],
            'page' => $built['page'],
            'pages' => $built['pages'],
            'perPage' => $built['perPage'],
            'total' => $built['total'],
        ]);
    }

    #[Route('/publicites/liste-fragment', name: 'manage_ads_index_fragment', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function indexFragment(Request $request, AdvertisementRepository $repository): Response
    {
        $built = $this->buildFilteredRows($request, $repository);

        return $this->json([
            'rowsHtml' => $this->renderView('manage/advertisements/_index_rows.html.twig', [
                'adRows' => $built['adRows'],
                'zoneKeys' => self::ZONE_INFO,
            ]),
            'footerInfo' => $built['total'] . ' publicité' . ($built['total'] != 1 ? 's' : '') . ' au total — page ' . $built['page'] . ' / ' . $built['pages'],
            'paginationHtml' => $this->renderView('manage/advertisements/_index_pagination.html.twig', ['page' => $built['page'], 'pages' => $built['pages']]),
        ]);
    }

    #[Route('/publicites/rechercher-titres', name: 'manage_ads_search_titles', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function searchTitles(Request $request, AdvertisementRepository $repository): Response
    {
        $term = trim((string) $request->query->get('q', ''));
        $results = mb_strlen($term) >= 1 ? $repository->searchByTitle($term) : [];

        return $this->json(['results' => array_map(fn (\App\Entity\Advertisement $ad) => [
            'id' => $ad->getId(),
            'title' => $ad->getTitle(),
        ], $results)]);
    }

    #[Route('/publicites/statistiques', name: 'manage_ads_stats', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function stats(AdvertisementRepository $repository): Response
    {
        $repository->expireOutdated();
        $allAds = $repository->findFiltered(null, null, null, 'title', 'ASC');

        $totalImpressions = array_sum(array_map(fn ($ad) => $ad->getImpressionCount() ?? 0, $allAds));
        $totalClicks = array_sum(array_map(fn ($ad) => $ad->getClickCount() ?? 0, $allAds));
        $globalCtr = $totalImpressions > 0 ? round($totalClicks / $totalImpressions * 100, 2) : 0;

        $byImpressions = $allAds;
        usort($byImpressions, fn ($a, $b) => ($b->getImpressionCount() ?? 0) <=> ($a->getImpressionCount() ?? 0));
        $topByImpressions = array_slice($byImpressions, 0, 10);

        $byClicks = $allAds;
        usort($byClicks, fn ($a, $b) => ($b->getClickCount() ?? 0) <=> ($a->getClickCount() ?? 0));
        $topByClicks = array_slice($byClicks, 0, 10);

        $statusCounts = [
            'active' => 0, 'scheduled' => 0, 'paused' => 0, 'expired' => 0,
        ];
        foreach ($allAds as $ad) {
            if (isset($statusCounts[$ad->getStatus()])) {
                $statusCounts[$ad->getStatus()]++;
            }
        }

        // Affichages/clics cumulés par page (dérivée de la zone principale de chaque pub).
        $byPage = [];
        foreach ($allAds as $ad) {
            $zoneInfo = self::ZONE_INFO[$ad->getZoneKey()] ?? null;
            $pageLabel = $zoneInfo['page'] ?? 'Autre';
            $byPage[$pageLabel]['impressions'] = ($byPage[$pageLabel]['impressions'] ?? 0) + ($ad->getImpressionCount() ?? 0);
            $byPage[$pageLabel]['clicks'] = ($byPage[$pageLabel]['clicks'] ?? 0) + ($ad->getClickCount() ?? 0);
        }

        return $this->render('manage/advertisements/stats.html.twig', [
            'totalAds' => count($allAds),
            'totalImpressions' => $totalImpressions,
            'totalClicks' => $totalClicks,
            'globalCtr' => $globalCtr,
            'topByImpressions' => $topByImpressions,
            'topByClicks' => $topByClicks,
            'statusCounts' => $statusCounts,
            'byPage' => $byPage,
        ]);
    }

    #[Route('/publicites/zones/{zoneKey}', name: 'manage_ad_zone_show', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function showZone(string $zoneKey, Request $request, AdvertisementRepository $repository): Response
    {
        if (!isset(self::ZONE_INFO[$zoneKey])) {
            throw $this->createNotFoundException('Zone inconnue.');
        }

        $status = $request->query->get('status') ?: null;
        $positionRaw = $request->query->get('position');
        $position = ('' !== (string) $positionRaw && null !== $positionRaw) ? (int) $positionRaw : null;
        $dateFrom = $request->query->get('date_from') ?: null;
        $dateTo = $request->query->get('date_to') ?: null;
        $sort = $request->query->get('sort', 'position');
        $dir = strtoupper($request->query->get('dir', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $ads = $repository->findByZonePlacement($zoneKey, $status, $position, $dateFrom, $dateTo);

        $rows = array_map(function (\App\Entity\Advertisement $ad) use ($zoneKey) {
            $clicks = 0;
            $impressions = 0;
            foreach ($ad->getZonePlacements() as $placement) {
                if ($placement->getZoneKey() === $zoneKey) {
                    $clicks += $placement->getClickCount();
                    $impressions += $placement->getImpressionCount();
                }
            }

            return ['ad' => $ad, 'clicks' => $clicks, 'impressions' => $impressions];
        }, $ads);

        if ('clicks' === $sort) {
            usort($rows, fn ($a, $b) => ('DESC' === $dir ? -1 : 1) * ($a['clicks'] <=> $b['clicks']));
        } elseif ('position' === $sort) {
            usort($rows, fn ($a, $b) => ('DESC' === $dir ? -1 : 1) * (($a['ad']->getPosition() ?? 0) <=> ($b['ad']->getPosition() ?? 0)));
        } elseif ('status' === $sort) {
            usort($rows, fn ($a, $b) => ('DESC' === $dir ? -1 : 1) * ($a['ad']->getStatus() <=> $b['ad']->getStatus()));
        } elseif ('startAt' === $sort) {
            usort($rows, fn ($a, $b) => ('DESC' === $dir ? -1 : 1) * ($a['ad']->getStartAt() <=> $b['ad']->getStartAt()));
        }

        $stats = [
            'total' => count($rows),
            'active' => count(array_filter($rows, fn ($r) => 'active' === $r['ad']->getStatus())),
            'totalClicks' => array_sum(array_column($rows, 'clicks')),
            'totalImpressions' => array_sum(array_column($rows, 'impressions')),
        ];

        $perPage = in_array((int) $request->query->get('perPage', 20), [10, 20, 50, 100], true)
            ? (int) $request->query->get('perPage', 20) : 20;
        $page = max(1, (int) $request->query->get('page', 1));
        $total = count($rows);
        $rows = array_slice($rows, ($page - 1) * $perPage, $perPage);

        return $this->render('manage/advertisements/zone_show.html.twig', [
            'zoneKey' => $zoneKey,
            'zoneInfo' => self::ZONE_INFO[$zoneKey],
            'rows' => $rows,
            'stats' => $stats,
            'currentStatus' => $status,
            'currentPosition' => $position,
            'currentDateFrom' => $dateFrom,
            'currentDateTo' => $dateTo,
            'currentSort' => $sort,
            'currentDir' => $dir,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'perPage' => $perPage,
            'total' => $total,
        ]);
    }

    /** Centralise filtrage/tri/pagination, réutilisé par la page complète et le fragment AJAX. */
    private function buildFilteredRows(Request $request, AdvertisementRepository $repository): array
    {
        $zoneKey = $request->query->get('zone') ?: null;
        $status = $request->query->get('status') ?: null;
        $term = $request->query->get('q') ?: null;
        $sitePage = $request->query->get('sitePage') ?: null;
        $sort = $request->query->get('sort', 'zoneKey');
        $dir = strtoupper($request->query->get('dir', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $ads = $repository->findFiltered($zoneKey, $status, $term, $sort, $dir);

        // Tri géré en PHP pour les colonnes calculées (pas de vraie colonne SQL derrière) :
        // "dimension" (dérivée de la zone), "clics" (somme des placements par zone) et "page" (dérivée de la zone).
        $adRows = array_map(function (\App\Entity\Advertisement $ad) {
            $totalClicks = 0;
            $totalImpressions = 0;
            foreach ($ad->getZonePlacements() as $placement) {
                $totalClicks += $placement->getClickCount();
                $totalImpressions += $placement->getImpressionCount();
            }
            $zoneInfo = self::ZONE_INFO[$ad->getZoneKey()] ?? null;

            return [
                'ad' => $ad,
                'totalClicks' => $totalClicks,
                'totalImpressions' => $totalImpressions,
                'width' => $zoneInfo['width'] ?? null,
                'height' => $zoneInfo['height'] ?? null,
                'sitePage' => $zoneInfo['page'] ?? '—',
            ];
        }, $ads);

        // Filtre "Page" — dérivé de la zone, pas une vraie colonne SQL, donc appliqué ici.
        if ($sitePage) {
            $adRows = array_values(array_filter($adRows, fn ($row) => $row['sitePage'] === $sitePage));
        }

        if ('dimension' === $sort) {
            usort($adRows, fn ($a, $b) => ('DESC' === $dir ? -1 : 1) * (($a['width'] ?? 0) <=> ($b['width'] ?? 0)));
        } elseif ('clicks' === $sort) {
            usort($adRows, fn ($a, $b) => ('DESC' === $dir ? -1 : 1) * ($a['totalClicks'] <=> $b['totalClicks']));
        } elseif ('sitePage' === $sort) {
            usort($adRows, fn ($a, $b) => ('DESC' === $dir ? -1 : 1) * ($a['sitePage'] <=> $b['sitePage']));
        }

        $allSitePages = array_values(array_unique(array_column(self::ZONE_INFO, 'page')));
        sort($allSitePages);

        $stats = [
            'total' => count($ads),
            'active' => count(array_filter($ads, fn ($ad) => 'active' === $ad->getStatus())),
            'scheduled' => count(array_filter($ads, fn ($ad) => 'scheduled' === $ad->getStatus())),
            'expired' => count(array_filter($ads, fn ($ad) => in_array($ad->getStatus(), ['expired', 'paused'], true))),
        ];

        $perPage = in_array((int) $request->query->get('perPage', 20), [10, 20, 50, 100], true)
            ? (int) $request->query->get('perPage', 20) : 20;
        $page = max(1, (int) $request->query->get('page', 1));
        $total = count($adRows);
        $adRows = array_slice($adRows, ($page - 1) * $perPage, $perPage);

        return [
            'adRows' => $adRows,
            'stats' => $stats,
            'allSitePages' => $allSitePages,
            'zoneKey' => $zoneKey,
            'status' => $status,
            'term' => $term,
            'sitePage' => $sitePage,
            'sort' => $sort,
            'dir' => $dir,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'perPage' => $perPage,
            'total' => $total,
        ];
    }

    #[Route('/publicites/nouveau', name: 'manage_ads_new', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function new(EntityManagerInterface $em): Response
    {
        return $this->render('manage/advertisements/form.html.twig', [
            'ad' => null,
            'zoneKeys' => self::ZONE_INFO,
            'selectedZoneKeys' => [],
            'sellers' => [],
            'categories' => $em->getRepository(Category::class)->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/publicites/nouveau', name: 'manage_ads_create', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $zoneKeys = $request->request->all('zone_keys');
        if (empty($zoneKeys)) {
            $this->addFlash('error', 'Choisis au moins une zone d\'affichage.');
            return $this->redirectToRoute('manage_ads_new');
        }

        if ($error = $this->validateImageForZones($request, $zoneKeys)) {
            $this->addFlash('error', $error);
            return $this->redirectToRoute('manage_ads_new');
        }

        try {
            $ad = new Advertisement();
            $this->hydrate($ad, $request, $em);
            $ad->setZoneKey($zoneKeys[0]);
            $em->persist($ad);

            $this->syncZonePlacements($ad, $zoneKeys, $em);
            $em->flush();
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Impossible d\'enregistrer la publicité : ' . $e->getMessage());
            return $this->redirectToRoute('manage_ads_new');
        }

        $this->addFlash('success', 'Publicité créée.');
        return $this->redirectToRoute('manage_ads_index');
    }

    #[Route('/publicites/{id}/modifier', name: 'manage_ads_edit', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function edit(Advertisement $ad, EntityManagerInterface $em): Response
    {
        $selectedZoneKeys = array_map(fn ($p) => $p->getZoneKey(), $ad->getZonePlacements()->toArray());

        return $this->render('manage/advertisements/form.html.twig', [
            'ad' => $ad,
            'zoneKeys' => self::ZONE_INFO,
            'selectedZoneKeys' => $selectedZoneKeys,
            'sellers' => [],
            'categories' => $em->getRepository(Category::class)->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/publicites/{id}/modifier', name: 'manage_ads_update', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(Advertisement $ad, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $zoneKeys = $request->request->all('zone_keys');
        if (empty($zoneKeys)) {
            $this->addFlash('error', 'Choisis au moins une zone d\'affichage.');
            return $this->redirectToRoute('manage_ads_edit', ['id' => $ad->getId()]);
        }

        if ($error = $this->validateImageForZones($request, $zoneKeys)) {
            $this->addFlash('error', $error);
            return $this->redirectToRoute('manage_ads_edit', ['id' => $ad->getId()]);
        }

        try {
            $this->hydrate($ad, $request, $em);
            $this->syncZonePlacements($ad, $zoneKeys, $em);
            $em->flush();
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Impossible de mettre à jour la publicité : ' . $e->getMessage());
            return $this->redirectToRoute('manage_ads_edit', ['id' => $ad->getId()]);
        }

        $this->addFlash('success', 'Publicité mise à jour.');
        return $this->redirectToRoute('manage_ads_index');
    }

    #[Route('/publicites/{id}/supprimer', name: 'manage_ads_delete', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Advertisement $ad, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($ad);
        $em->flush();

        $this->addFlash('success', 'Publicité supprimée.');
        return $this->redirectToRoute('manage_ads_index');
    }


    /** Crée les placements pour les zones nouvellement cochées, retire ceux décochés. */
    private function syncZonePlacements(Advertisement $ad, array $zoneKeys, EntityManagerInterface $em): void
    {
        $existing = [];
        foreach ($ad->getZonePlacements() as $placement) {
            $existing[$placement->getZoneKey()] = $placement;
        }

        foreach ($zoneKeys as $zoneKey) {
            if (!isset($existing[$zoneKey])) {
                $placement = new \App\Entity\AdvertisementZonePlacement();
                $placement->setZoneKey($zoneKey);
                $ad->addZonePlacement($placement);
                $em->persist($placement);
            }
        }

        foreach ($existing as $zoneKey => $placement) {
            if (!in_array($zoneKey, $zoneKeys, true)) {
                $ad->removeZonePlacement($placement);
                $em->remove($placement);
            }
        }

        // zoneKey reste renseigné comme "zone principale" — première cochée, utilisée pour l'affichage simplifié en liste admin.
        $ad->setZoneKey($zoneKeys[0]);
    }

    /** Bloque si l'image envoyée ne correspond pas exactement à CHAQUE zone cochée ayant une contrainte définie. */
    private function validateImageForZones(Request $request, array $zoneKeys): ?string
    {
        $file = $request->files->get('image_file');
        if (!$file) {
            return null;
        }

        $dimensions = @getimagesize($file->getPathname());
        if (!$dimensions) {
            return 'Impossible de lire les dimensions de l\'image envoyée.';
        }
        [$actualWidth, $actualHeight] = $dimensions;

        $mismatches = [];
        foreach ($zoneKeys as $zoneKey) {
            $expected = self::ZONE_INFO[$zoneKey] ?? null;
            if (!$expected || null === $expected['width'] || null === $expected['height']) {
                continue;
            }
            if ($actualWidth !== $expected['width'] || $actualHeight !== $expected['height']) {
                $mismatches[] = sprintf('%s (attendu %dx%d)', $expected['label'], $expected['width'], $expected['height']);
            }
        }

        if (!empty($mismatches)) {
            return sprintf(
                'L\'image envoyée fait %dx%d px, ce qui ne correspond pas à : %s.',
                $actualWidth, $actualHeight, implode(', ', $mismatches)
            );
        }

        return null;
    }

    private function hydrate(Advertisement $ad, Request $request, EntityManagerInterface $em): void
    {
        $title = (string) $request->request->get('title');
        $ad->setTitle($title);
        $ad->setDescription($request->request->get('description') ?: null);

        if (null === $ad->getSlug()) {
            $slugger = new \Symfony\Component\String\Slugger\AsciiSlugger();
            $ad->setSlug(strtolower((string) $slugger->slug($title)) . '-' . uniqid());
        }
        $ad->setTargetSpace($request->request->get('target_space', 'public'));
        $ad->setTargetUrl($request->request->get('target_url') ?: null);
        $ad->setOpenInNewTab((bool) $request->request->get('open_in_new_tab'));
        $ad->setPosition($request->request->get('position') !== '' ? (int) $request->request->get('position') : null);
        $ad->setStatus($request->request->get('status', 'scheduled'));

        $startAt = $request->request->get('start_at');
        $endAt = $request->request->get('end_at');

        $startAtDate = $startAt ? new \DateTimeImmutable($startAt) : null;
        $endAtDate = $endAt ? new \DateTimeImmutable($endAt) : null;

        if ($startAtDate && $endAtDate && $endAtDate <= $startAtDate) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début.');
        }

        if ($startAtDate) {
            $ad->setStartAt($startAtDate);
        }
        $ad->setEndAt($endAtDate);

        $ad->setIsPaid((bool) $request->request->get('is_paid'));
        $priceAmount = $request->request->get('price_amount_usd');
        $ad->setPriceAmountUsd($ad->isPaid() && $priceAmount !== '' ? $priceAmount : null);

        $categoryId = $request->request->get('related_category_id');
        $ad->setRelatedCategory($categoryId ? $em->getRepository(Category::class)->find((int) $categoryId) : null);

        $sellerId = $request->request->get('advertiser_id');
        $ad->setAdvertiser($sellerId ? $em->getRepository(SellerProfile::class)->find((int) $sellerId) : null);

        $file = $request->files->get('image_file');
        if ($file) {
            $ad->setImageFile($file);
        }
    }
}