<?php

namespace App\Controller\Manage;

use App\Entity\SeoOverride;
use App\Repository\SeoOverrideRepository;
use App\Repository\ShareEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SeoManagementController extends AbstractController
{
    /** Pages statiques connues du site — proposées directement dans le formulaire de création. */
    public const STATIC_PAGES = [
        'homepage' => 'Accueil',
        'about' => 'À propos',
        'contact' => 'Contact',
        'cgu' => 'Conditions générales d\'utilisation',
        'privacy' => 'Politique de confidentialité',
    ];

    public const ENTITY_TYPES = [
        'static_page' => 'Page statique',
        'product' => 'Produit',
        'category' => 'Catégorie',
        'seller' => 'Boutique vendeur',
    ];

    #[Route('/referencement', name: 'manage_seo_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(Request $request, SeoOverrideRepository $repository): Response
    {
        $entityType = $request->query->get('type') ?: null;
        $term = $request->query->get('q') ?: null;
        $overrides = $repository->findFiltered($entityType, $term);

        return $this->render('manage/seo/index.html.twig', [
            'overrides' => $overrides,
            'entityTypes' => self::ENTITY_TYPES,
            'currentType' => $entityType,
            'searchTerm' => $term,
            'stats' => [
                'total' => count($repository->findFiltered(null, null)),
                'staticPages' => count($repository->findFiltered('static_page', null)),
            ],
        ]);
    }

    #[Route('/referencement/nouveau', name: 'manage_seo_new', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('manage/seo/form.html.twig', [
            'override' => null,
            'entityTypes' => self::ENTITY_TYPES,
            'staticPages' => self::STATIC_PAGES,
        ]);
    }

    #[Route('/referencement/nouveau', name: 'manage_seo_create', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $override = new SeoOverride();
        $this->hydrate($override, $request, $em);
        $em->persist($override);
        $em->flush();

        $this->addFlash('success', 'Fiche SEO créée.');
        return $this->redirectToRoute('manage_seo_index');
    }

    #[Route('/referencement/partages', name: 'manage_share_stats', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function shareStats(ShareEventRepository $repository, \App\Repository\ProductRepository $productRepository): Response
    {
        $rows = $repository->countGroupedByPageAndPlatform();
        $byPlatform = $repository->countByPlatform();

        // Regroupe par page (une page peut avoir plusieurs lignes, une par plateforme).
        $byPage = [];
        foreach ($rows as $row) {
            $key = $row['entityType'] . ':' . ($row['entityId'] ?? $row['pageKey']);
            $byPage[$key]['label'] = $row['adminLabel'] ?: ($row['pageKey'] ?: ($row['entityType'] . ' #' . $row['entityId']));
            $byPage[$key]['entityType'] = $row['entityType'];
            $byPage[$key]['entityId'] = $row['entityId'];
            $byPage[$key]['platforms'][$row['platform']] = $row['total'];
            $byPage[$key]['total'] = ($byPage[$key]['total'] ?? 0) + $row['total'];
        }
        uasort($byPage, fn ($a, $b) => $b['total'] <=> $a['total']);

        // Pour les partages de fiches produit, on va chercher image/réf./vendeur/province une seule fois par produit.
        foreach ($byPage as $key => &$row) {
            $row['image'] = null;
            $row['kbzReference'] = null;
            $row['articleUrl'] = null;
            $row['sellerName'] = null;
            $row['province'] = null;

            if ('product' !== $row['entityType'] || !$row['entityId']) {
                continue;
            }

            $product = $productRepository->find($row['entityId']);
            if (!$product) {
                continue;
            }

            $firstImage = $product->getImages()->first();
            $row['image'] = $firstImage ?: null;
            $row['kbzReference'] = $product->getKongobazarReference();
            $row['articleUrl'] = $this->generateUrl('manage_products_show', ['id' => $product->getId()]);

            if ($product->getSellerProfile()) {
                $row['sellerName'] = $product->getSellerProfile()->getDisplayName();

                $provinces = [];
                foreach ($product->getSellerProfile()->getDeliveryZones() as $zone) {
                    $province = 1 === $zone->getLevel() ? $zone : $zone->getAncestorAtLevel(1);
                    if ($province && !in_array($province->getName(), $provinces, true)) {
                        $provinces[] = $province->getName();
                    }
                }
                $row['province'] = $provinces ? implode(', ', $provinces) : null;
            }
        }
        unset($row);

        return $this->render('manage/seo/share_stats.html.twig', [
            'byPage' => $byPage,
            'byPlatform' => $byPlatform,
            'totalShares' => array_sum(array_column($byPlatform, 'total')),
        ]);
    }

    #[Route('/referencement/{id}', name: 'manage_seo_show', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(SeoOverride $override): Response
    {
        $finalTitle = $override->getMetaTitle() ?: 'KongoBazar';
        $finalDescription = $override->getMetaDescription() ?: 'KongoBazar — la marketplace de référence en RDC';
        $finalOgTitle = $override->getOgTitle() ?: $finalTitle;
        $finalOgDescription = $override->getOgDescription() ?: $finalDescription;

        $publicUrl = null;
        if ('static_page' === $override->getEntityType() && 'homepage' === $override->getPageKey()) {
            $publicUrl = $this->generateUrl('public_home', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return $this->render('manage/seo/show.html.twig', [
            'override' => $override,
            'entityTypes' => self::ENTITY_TYPES,
            'finalTitle' => $finalTitle,
            'finalDescription' => $finalDescription,
            'finalOgTitle' => $finalOgTitle,
            'finalOgDescription' => $finalOgDescription,
            'publicUrl' => $publicUrl,
        ]);
    }

    #[Route('/referencement/{id}/modifier', name: 'manage_seo_edit', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function edit(SeoOverride $override): Response
    {
        return $this->render('manage/seo/form.html.twig', [
            'override' => $override,
            'entityTypes' => self::ENTITY_TYPES,
            'staticPages' => self::STATIC_PAGES,
        ]);
    }

    #[Route('/referencement/{id}/modifier', name: 'manage_seo_update', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(SeoOverride $override, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $this->hydrate($override, $request, $em);
        $em->flush();

        $this->addFlash('success', 'Fiche SEO mise à jour.');
        return $this->redirectToRoute('manage_seo_index');
    }

    #[Route('/referencement/{id}/supprimer', name: 'manage_seo_delete', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(SeoOverride $override, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($override);
        $em->flush();

        $this->addFlash('success', 'Fiche SEO supprimée.');
        return $this->redirectToRoute('manage_seo_index');
    }

    private function hydrate(SeoOverride $override, Request $request, EntityManagerInterface $em): void
    {
        $entityType = $request->request->get('entity_type', 'static_page');
        $override->setEntityType($entityType);

        if ('static_page' === $entityType) {
            $pageKey = $request->request->get('page_key') ?: null;
            $override->setPageKey($pageKey);
            $override->setEntityId(null);
            $override->setAdminLabel(self::STATIC_PAGES[$pageKey] ?? $pageKey);
        } else {
            $entityId = $request->request->get('entity_id') ? (int) $request->request->get('entity_id') : null;
            $override->setEntityId($entityId);
            $override->setPageKey(null);
            $override->setAdminLabel($request->request->get('admin_label') ?: null);
        }

        $override->setMetaTitle($request->request->get('meta_title') ?: null);
        $override->setMetaDescription($request->request->get('meta_description') ?: null);
        $override->setMetaKeywords($request->request->get('meta_keywords') ?: null);
        $override->setOgTitle($request->request->get('og_title') ?: null);
        $override->setOgDescription($request->request->get('og_description') ?: null);
        $override->setNoIndex((bool) $request->request->get('no_index'));
        $override->setNoFollow((bool) $request->request->get('no_follow'));

        $file = $request->files->get('og_image_file');
        if ($file) {
            $override->setOgImageFile($file);
        }
    }
}
