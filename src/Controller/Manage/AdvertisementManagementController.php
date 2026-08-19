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
    /** Chaque zone : libellé + dimensions exactes attendues en pixels (null = pas encore de contrainte définie). */
    public const ZONE_INFO = [
        'homepage_hero_main' => ['label' => 'Hero — carrousel principal', 'width' => 754, 'height' => 420],
        'homepage_hero_side_top' => ['label' => 'Hero — bannière latérale haute', 'width' => 270, 'height' => 200],
        'homepage_hero_side_bottom' => ['label' => 'Hero — bannière latérale basse', 'width' => 270, 'height' => 200],
        'sidebar_top' => ['label' => 'Colonne gauche — haut', 'width' => 270, 'height' => 200],
        'sidebar_middle' => ['label' => 'Colonne gauche — milieu', 'width' => 270, 'height' => 200],
        'homepage_promo_strip' => ['label' => 'Bandeau promo', 'width' => 1044, 'height' => 120],
        'homepage_center_banner' => ['label' => 'Bannière centrale', 'width' => 1044, 'height' => 250],
        'category_block_banner' => ['label' => 'Bannière bas de bloc catégorie (liée à une catégorie précise)', 'width' => null, 'height' => null],
        'homepage_lifestyle_left' => ['label' => 'Mosaïque lifestyle — gauche', 'width' => 255, 'height' => 220],
        'homepage_lifestyle_center' => ['label' => 'Mosaïque lifestyle — centre', 'width' => 510, 'height' => 220],
        'homepage_lifestyle_right' => ['label' => 'Mosaïque lifestyle — droite', 'width' => 255, 'height' => 220],
        'footer_social_banner' => ['label' => 'Footer — bannière sociale', 'width' => null, 'height' => null],
        'footer_mosaic' => ['label' => 'Footer — mosaïque photos', 'width' => null, 'height' => null],
        'footer_callus_photo' => ['label' => 'Footer — photo "Appelez-nous"', 'width' => null, 'height' => null],
        'mega_menu_catalogue_1' => ['label' => 'Méga-menu — bannière 1', 'width' => 320, 'height' => 180],
        'mega_menu_catalogue_2' => ['label' => 'Méga-menu — bannière 2', 'width' => 320, 'height' => 180],
        'mega_menu_catalogue_3' => ['label' => 'Méga-menu — bannière 3', 'width' => 320, 'height' => 180],
        'mega_menu_catalogue_4' => ['label' => 'Méga-menu — bannière 4', 'width' => 320, 'height' => 180],
        'rayon_flyout_ad_droite' => ['label' => 'Flyout rayon — position droite (liée à une catégorie précise)', 'width' => 220, 'height' => 400],
        'rayon_flyout_ad_bas' => ['label' => 'Flyout rayon — position bas (liée à une catégorie précise)', 'width' => 480, 'height' => 120],
        'mobile_top_rayons_offcanvas' => ['label' => 'Tiroir "Top Rayons" mobile', 'width' => 335, 'height' => 100],
    ];

    #[Route('/publicites', name: 'manage_ads_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(Request $request, AdvertisementRepository $repository): Response
    {
        $repository->expireOutdated();

        $zoneKey = $request->query->get('zone') ?: null;
        $status = $request->query->get('status') ?: null;
        $term = $request->query->get('q') ?: null;
        $sort = $request->query->get('sort', 'zoneKey');
        $dir = strtoupper($request->query->get('dir', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $ads = $repository->findFiltered($zoneKey, $status, $term, $sort, $dir);

        // Tri géré en PHP pour les colonnes calculées (pas de vraie colonne SQL derrière) :
        // "dimension" (dérivée de la zone) et "clics" (somme des placements par zone).
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
            ];
        }, $ads);

        if ('dimension' === $sort) {
            usort($adRows, fn ($a, $b) => ('DESC' === $dir ? -1 : 1) * (($a['width'] ?? 0) <=> ($b['width'] ?? 0)));
        } elseif ('clicks' === $sort) {
            usort($adRows, fn ($a, $b) => ('DESC' === $dir ? -1 : 1) * ($a['totalClicks'] <=> $b['totalClicks']));
        }

        return $this->render('manage/advertisements/index.html.twig', [
            'adRows' => $adRows,
            'zoneKeys' => self::ZONE_INFO,
            'currentZone' => $zoneKey,
            'currentStatus' => $status,
            'searchTerm' => $term,
            'currentSort' => $sort,
            'currentDir' => $dir,
        ]);
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