<?php

namespace App\Controller\Manage;

use App\Entity\Campaign;
use App\Entity\CampaignPriorityProduct;
use App\Entity\CampaignPrioritySeller;
use App\Entity\Product;
use App\Entity\ProProfile;
use App\Entity\SellerProfile;
use App\Entity\StoreProfile;
use App\Repository\AdvertisementRepository;
use App\Repository\ProductRepository;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use App\Repository\SellerProfileRepository;
use App\Repository\CampaignRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CampaignController extends AbstractController
{
    #[Route('/campagnes', name: 'manage_campaign_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(CampaignRepository $repository, ProductRepository $productRepository, EntityManagerInterface $em): Response
    {
        $campaigns = $repository->findAllOrdered();

        $stats = [];
        foreach ($campaigns as $campaign) {
            $articleCount = 'solde' === $campaign->getType() ? count($productRepository->findAllActiveDeals()) : null;

            $soldCount = $em->createQueryBuilder()
                ->select('COALESCE(SUM(oi.quantity), 0)')
                ->from(\App\Entity\OrderItem::class, 'oi')
                ->join('oi.order', 'o')
                ->andWhere('o.createdAt >= :start')
                ->andWhere('o.createdAt < :end')
                ->setParameter('start', $campaign->getStartAt())
                ->setParameter('end', $campaign->getEndAt())
                ->getQuery()
                ->getSingleScalarResult();

            $stats[$campaign->getId()] = [
                'articleCount' => $articleCount,
                'soldCount' => (int) $soldCount,
            ];
        }

        return $this->render('manage/campaign/index.html.twig', [
            'campaigns' => $campaigns,
            'stats' => $stats,
            'types' => Campaign::TYPES,
        ]);
    }

    #[Route('/campagnes/ajouter', name: 'manage_campaign_new', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('manage/campaign/form.html.twig', ['campaign' => null, 'types' => Campaign::TYPES]);
    }

    #[Route('/campagnes/{id}/modifier', name: 'manage_campaign_edit', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function edit(Campaign $campaign): Response
    {
        return $this->render('manage/campaign/form.html.twig', ['campaign' => $campaign, 'types' => Campaign::TYPES]);
    }

    #[Route('/campagnes/enregistrer', name: 'manage_campaign_create', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        return $this->save(new Campaign(), $request, $em, true);
    }

    #[Route('/campagnes/{id}/enregistrer', name: 'manage_campaign_update', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(Campaign $campaign, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        return $this->save($campaign, $request, $em, false);
    }

    /** Nettoie les textes venant du formulaire : positions/tailles/couleurs validées, 8 max. */
    private function sanitizeBannerTexts(array $raw): array
    {
        $clean = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $text = trim((string) ($row['text'] ?? ''));
            if ('' === $text) {
                continue;
            }
            $pos = (string) ($row['pos'] ?? '');
            $size = (string) ($row['size'] ?? '');
            $color = (string) ($row['color'] ?? '');
            $bg = (string) ($row['bg'] ?? '');

            $clean[] = [
                'text' => mb_substr($text, 0, 120),
                'pos' => in_array($pos, Campaign::BADGE_POSITIONS, true) ? $pos : 'middle-left',
                'size' => in_array($size, ['s', 'm', 'l', 'xl'], true) ? $size : 'm',
                'color' => preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#ffffff',
                'bg' => preg_match('/^#[0-9a-fA-F]{6}$/', $bg) ? $bg : null,
            ];
            if (count($clean) >= 8) {
                break;
            }
        }
        return $clean;
    }

    private function save(Campaign $campaign, Request $request, EntityManagerInterface $em, bool $isNew): RedirectResponse
    {
        $title = trim((string) $request->request->get('title', ''));
        $startAt = $request->request->get('start_at');
        $endAt = $request->request->get('end_at');

        if ('' === $title || !$startAt || !$endAt) {
            $this->addFlash('error', 'Le titre et la période (début/fin) sont obligatoires.');
            return $this->redirectToRoute($isNew ? 'manage_campaign_new' : 'manage_campaign_edit', $isNew ? [] : ['id' => $campaign->getId()]);
        }

        $startAtDate = new \DateTimeImmutable($startAt);
        $endAtDate = new \DateTimeImmutable($endAt);
        if ($endAtDate <= $startAtDate) {
            $this->addFlash('error', 'La date de fin doit être après la date de début.');
            return $this->redirectToRoute($isNew ? 'manage_campaign_new' : 'manage_campaign_edit', $isNew ? [] : ['id' => $campaign->getId()]);
        }

        $campaign->setType((string) $request->request->get('type', 'solde'));
        $campaign->setCustomTypeLabel($request->request->get('custom_type_label') ?: null);
        $campaign->setTitle($title);
        $campaign->setStartAt($startAtDate);
        $campaign->setEndAt($endAtDate);
        $campaign->setBatchSize((int) $request->request->get('batch_size', 12));
        $campaign->setBadgePosition((string) $request->request->get('badge_position', 'middle-right'));
        $campaign->setBadgeVisible($request->request->getBoolean('badge_visible'));
        $campaign->setBannerEnabled($request->request->getBoolean('banner_enabled'));
        $campaign->setTextsEnabled($request->request->getBoolean('texts_enabled'));
        $campaign->setThemeTopEnabled($request->request->getBoolean('theme_top_enabled'));
        $campaign->setThemeEnabled($request->request->getBoolean('theme_enabled'));
        $campaign->setPriorityProductsEnabled($request->request->getBoolean('priority_products_enabled'));
        $campaign->setPrioritySellersEnabled($request->request->getBoolean('priority_sellers_enabled'));
        $campaign->setTopCategoriesEnabled($request->request->getBoolean('top_categories_enabled'));
        $campaign->setBatchBannersEnabled($request->request->getBoolean('batch_banners_enabled'));
        $campaign->setBatchBannerEvery((int) $request->request->get('batch_banner_every', 1));
        $campaign->setBatchBannerMode((string) $request->request->get('batch_banner_mode', 'random'));
        $campaign->setBatchBannerAdIds($request->request->all('batch_banner_ads'));
        $campaign->setBannerTexts($this->sanitizeBannerTexts($request->request->all('texts')));

        $campaign->setThemeMode((string) $request->request->get('theme_mode', 'width-repeat'));

        if ($request->request->getBoolean('theme_top_image_remove')) {
            $campaign->setThemeTopImageFile(null);
            $campaign->setThemeTopImageName(null);
        }
        $themeTopFile = $request->files->get('theme_top_image');
        if ($themeTopFile) {
            if ($themeTopFile->isValid()) {
                $campaign->setThemeTopImageFile($themeTopFile);
            } else {
                $this->addFlash('error', 'Décor du haut non enregistré : ' . $themeTopFile->getErrorMessage());
            }
        }
        $themeBg = (string) $request->request->get('theme_bg_color', '');
        $campaign->setThemeBgColor(preg_match('/^#[0-9a-fA-F]{6}$/', $themeBg) ? $themeBg : null);
        if ($request->request->getBoolean('theme_image_remove')) {
            $campaign->setThemeImageFile(null);
            $campaign->setThemeImageName(null);
        }
        $themeFile = $request->files->get('theme_image');
        if ($themeFile) {
            if ($themeFile->isValid()) {
                $campaign->setThemeImageFile($themeFile);
            } else {
                $this->addFlash('error', 'Image du décor non enregistrée : ' . $themeFile->getErrorMessage());
            }
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('banner_image');
        if ($file) {
            if ($file->isValid()) {
                $campaign->setBannerImageFile($file);
            } else {
                $this->addFlash('error', 'Bannière non enregistrée : ' . $file->getErrorMessage());
            }
        }

        $this->syncPriorities($campaign, $request, $em);

        if ($isNew) {
            $campaign->setActive(true);
            $em->persist($campaign);
        }
        $em->flush();

        $this->addFlash('success', 'Campagne ' . ($isNew ? 'créée' : 'mise à jour') . '.');
        return $this->redirectToRoute('manage_campaign_index');
    }

    private function isProOrStore(?SellerProfile $seller): bool
    {
        return $seller instanceof StoreProfile || $seller instanceof ProProfile;
    }

    /** Reconstruit les produits et vendeurs prioritaires à partir du formulaire (ordre = ordre affiché). */
    private function syncPriorities(Campaign $campaign, Request $request, EntityManagerInterface $em): void
    {
        $campaign->getPriorityProducts()->clear();
        $campaign->getPrioritySellers()->clear();

        $seen = [];
        $position = 0;
        foreach ($request->request->all('priority_products') as $rawId) {
            $id = (int) $rawId;
            if ($id <= 0 || isset($seen[$id])) {
                continue;
            }
            $product = $em->getRepository(Product::class)->find($id);
            if (!$product || 'active' !== $product->getStatus() || !$this->isProOrStore($product->getSellerProfile())) {
                continue;
            }
            $seen[$id] = true;
            $campaign->addPriorityProduct((new CampaignPriorityProduct())->setProduct($product)->setPosition($position++));
        }

        $seen = [];
        $position = 0;
        foreach ($request->request->all('priority_sellers') as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0 || isset($seen[$id])) {
                continue;
            }
            $seller = $em->getRepository(SellerProfile::class)->find($id);
            if (!$seller || !$this->isProOrStore($seller)) {
                continue;
            }
            $seen[$id] = true;
            $campaign->addPrioritySeller(
                (new CampaignPrioritySeller())
                    ->setSellerProfile($seller)
                    ->setProductLimit((int) ($row['limit'] ?? 4))
                    ->setPosition($position++)
            );
        }
    }

    /** Bannières de la zone "entre les lots" (actives ou programmées) proposées dans le formulaire de campagne. */
    #[Route('/campagnes/bannieres-lots', name: 'manage_campaign_batch_banners', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function batchBanners(AdvertisementRepository $advertisementRepository, UploaderHelper $uploaderHelper): Response
    {
        $ads = $advertisementRepository->createQueryBuilder('a')
            ->innerJoin('a.zonePlacements', 'zp')
            ->andWhere('zp.zoneKey = :zoneKey')->setParameter('zoneKey', 'solde_between_batches')
            ->andWhere('a.targetSpace = :space')->setParameter('space', 'public')
            ->andWhere('a.status IN (:statuses)')->setParameter('statuses', ['active', 'scheduled'])
            ->orderBy('a.position', 'ASC')
            ->getQuery()
            ->getResult();

        $statusLabels = ['active' => 'Active', 'scheduled' => 'Programmée'];

        return $this->json(['results' => array_map(static fn ($a) => [
            'id' => $a->getId(),
            'title' => $a->getTitle(),
            'status' => $statusLabels[$a->getStatus()] ?? $a->getStatus(),
            'imageUrl' => $a->getImageName() ? $uploaderHelper->asset($a, 'imageFile') : null,
        ], $ads)]);
    }

    /** Recherche de produits actifs de boutiques / vendeurs Pro (autocomplétion du formulaire). */
    #[Route('/campagnes/rechercher-produits', name: 'manage_campaign_search_products', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function searchProducts(Request $request, ProductRepository $productRepository): Response
    {
        $term = trim((string) $request->query->get('q', ''));
        if (mb_strlen($term) < 2) {
            return $this->json(['results' => []]);
        }

        $qb = $productRepository->createQueryBuilder('p')
            ->join('p.sellerProfile', 's')
            ->andWhere('p.status = :status')->setParameter('status', 'active')
            ->andWhere('(s INSTANCE OF App\Entity\StoreProfile OR s INSTANCE OF App\Entity\ProProfile)')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults(20);

        if (preg_match('/^(?:KBZ-?)?0*(\d+)$/i', $term, $m)) {
            $qb->andWhere('(p.id = :pid OR p.title LIKE :t)')
                ->setParameter('pid', (int) $m[1])
                ->setParameter('t', '%' . $term . '%');
        } else {
            $qb->andWhere('p.title LIKE :t')->setParameter('t', '%' . $term . '%');
        }

        return $this->json(['results' => array_map(function (Product $p) {
            $sub = $p->getKongobazarReference() . ' — ' . $p->getSellerProfile()->getDisplayName()
                . (null === $p->getActiveDiscountPercent() ? ' — hors solde' : '');
            return [
                'id' => $p->getId(),
                'title' => $p->getTitle(),
                'sub' => $sub,
                'label' => $p->getTitle() . ' · ' . $sub,
                'imageUrl' => $p->getImages()->count() > 0 ? '/media/products/' . $p->getImages()->first()->getImageName() : null,
            ];
        }, $qb->getQuery()->getResult())]);
    }

    /** Recherche de boutiques / vendeurs Pro actifs (autocomplétion du formulaire). */
    #[Route('/campagnes/rechercher-vendeurs', name: 'manage_campaign_search_sellers', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function searchSellers(Request $request, SellerProfileRepository $sellerRepository): Response
    {
        $term = trim((string) $request->query->get('q', ''));

        $qb = $sellerRepository->createQueryBuilder('s')
            ->andWhere('(s INSTANCE OF App\Entity\StoreProfile OR s INSTANCE OF App\Entity\ProProfile)')
            ->andWhere('s.status = :status')->setParameter('status', 'active')
            ->orderBy('s.displayName', 'ASC')
            ->setMaxResults(20);

        if ('' !== $term) {
            $qb->andWhere('(s.displayName LIKE :t OR s.referenceNumber LIKE :t)')->setParameter('t', '%' . $term . '%');
        }

        return $this->json(['results' => array_map(function (SellerProfile $s) {
            $sub = $s->getTypeLabel() . ($s->getReferenceNumber() ? ' — ' . $s->getReferenceNumber() : '');
            return [
                'id' => $s->getId(),
                'title' => $s->getDisplayName(),
                'sub' => $sub,
                'label' => $s->getDisplayName() . ' · ' . $sub,
            ];
        }, $qb->getQuery()->getResult())]);
    }

    #[Route('/campagnes/{id}/basculer', name: 'manage_campaign_toggle', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(Campaign $campaign, CampaignRepository $repository, EntityManagerInterface $em): Response
    {
        $newActiveState = !$campaign->isActive();
        $campaign->setActive($newActiveState);

        // Une seule campagne active à la fois : en activer une désactive automatiquement
        // toutes les autres.
        $deactivatedIds = [];
        if ($newActiveState) {
            foreach ($repository->findAllOrdered() as $other) {
                if ($other->getId() !== $campaign->getId() && $other->isActive()) {
                    $other->setActive(false);
                    $deactivatedIds[] = $other->getId();
                }
            }
        }

        $em->flush();

        return $this->json([
            'ok' => true,
            'active' => $campaign->isActive(),
            'statusLabel' => $campaign->getStatusLabel(),
            'deactivatedIds' => $deactivatedIds,
        ]);
    }

    #[Route('/campagnes/{id}/supprimer', name: 'manage_campaign_remove', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function remove(Campaign $campaign, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($campaign);
        $em->flush();

        $this->addFlash('success', 'Campagne supprimée.');
        return $this->redirectToRoute('manage_campaign_index');
    }
}
