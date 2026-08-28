<?php

namespace App\Controller\Manage;

use App\Entity\Brand;
use App\Entity\Category;
use App\Entity\CategoryAttribute;
use App\Entity\CategoryAttributeOption;
use App\Entity\Characteristic;
use App\Entity\PartCatalogAttributeValue;
use App\Entity\PartCatalogBrandCompatibility;
use App\Entity\PartCatalogEntry;
use App\Entity\PartCatalogOemCode;
use App\Repository\CategoryAttributeRepository;
use App\Repository\CategoryRepository;
use App\Repository\PartCatalogEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PartCatalogManagementController extends AbstractController
{
    #[Route('/pieces-catalogue', name: 'manage_part_catalog_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(Request $request, PartCatalogEntryRepository $repository, CategoryRepository $categoryRepository): Response
    {
        $status = $request->query->get('status') ?: null;
        $term = $request->query->get('q') ?: null;

        $categoryFilterId = $request->query->get('category') ? (int) $request->query->get('category') : null;
        $showAuto = $request->query->get('showAuto', '1') === '1';
        $showMoto = $request->query->get('showMoto', '1') === '1';

        $categoryIds = null;
        if ($categoryFilterId) {
            $selectedCategory = $categoryRepository->find($categoryFilterId);
            if ($selectedCategory) {
                $categoryIds = array_merge(
                    [$selectedCategory->getId()],
                    array_map(fn ($c) => $c->getId(), $selectedCategory->getDescendantCategories())
                );
            }
        } else {
            $categoryIds = $categoryRepository->findCategoryIdsByPartType($showAuto, $showMoto) ?: null;
        }

        $sort = $request->query->get('sort', 'name');
        $dir = $request->query->get('dir', 'ASC');
        $allRows = $this->buildCatalogRows($repository->findFiltered($status, $term, $categoryIds), $repository, $sort, $dir, $categoryRepository->findAllPartTypeMap());

        $stats = [
            'total' => count($repository->findFiltered(null, null, null)),
            'auto' => count(array_filter($allRows, fn ($r) => $r['partType'] === 'auto')),
            'moto' => count(array_filter($allRows, fn ($r) => $r['partType'] === 'moto')),
            'validated' => count(array_filter($allRows, fn ($r) => $r['entry']->getStatus() === 'validated')),
        ];

        $perPage = in_array((int) $request->query->get('perPage', 20), [10, 20, 50, 100], true)
            ? (int) $request->query->get('perPage', 20) : 20;
        $page = max(1, (int) $request->query->get('page', 1));
        $total = count($allRows);
        $rows = array_slice($allRows, ($page - 1) * $perPage, $perPage);

        return $this->render('manage/part_catalog/index.html.twig', [
            'stats' => $stats,
            'rows' => $rows,
            'currentStatus' => $status,
            'searchTerm' => $term,
            'currentCategoryId' => $categoryFilterId,
            'currentSort' => $sort,
            'currentDir' => $dir,
            'currentShowAuto' => $showAuto,
            'currentShowMoto' => $showMoto,
            'partFilterCategories' => $categoryRepository->findPartFilterStartingCategories(),
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'perPage' => $perPage,
            'total' => $total,
        ]);
    }

    #[Route('/pieces-catalogue/liste-fragment', name: 'manage_part_catalog_index_fragment', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function indexFragment(Request $request, PartCatalogEntryRepository $repository, CategoryRepository $categoryRepository): Response
    {
        $status = $request->query->get('status') ?: null;
        $term = $request->query->get('q') ?: null;

        $categoryFilterId = $request->query->get('category') ? (int) $request->query->get('category') : null;
        $showAuto = $request->query->get('showAuto', '1') === '1';
        $showMoto = $request->query->get('showMoto', '0') === '1';

        $categoryIds = null;
        if ($categoryFilterId) {
            $selectedCategory = $categoryRepository->find($categoryFilterId);
            if ($selectedCategory) {
                $categoryIds = array_merge(
                    [$selectedCategory->getId()],
                    array_map(fn ($c) => $c->getId(), $selectedCategory->getDescendantCategories())
                );
            }
        } else {
            $categoryIds = $categoryRepository->findCategoryIdsByPartType($showAuto, $showMoto) ?: null;
        }

        $sort = $request->query->get('sort', 'name');
        $dir = $request->query->get('dir', 'ASC');
        $allRows = $this->buildCatalogRows($repository->findFiltered($status, $term, $categoryIds), $repository, $sort, $dir, $categoryRepository->findAllPartTypeMap());

        $perPage = in_array((int) $request->query->get('perPage', 20), [10, 20, 50, 100], true)
            ? (int) $request->query->get('perPage', 20) : 20;
        $page = max(1, (int) $request->query->get('page', 1));
        $total = count($allRows);
        $pages = max(1, (int) ceil($total / $perPage));
        $rows = array_slice($allRows, ($page - 1) * $perPage, $perPage);

        return $this->json([
            'rowsHtml' => $this->renderView('manage/part_catalog/_index_rows.html.twig', ['rows' => $rows]),
            'footerInfo' => $total . ' fiche' . ($total != 1 ? 's' : '') . ' — page ' . $page . ' / ' . $pages,
            'paginationHtml' => $this->renderView('manage/part_catalog/_index_pagination.html.twig', ['page' => $page, 'pages' => $pages]),
        ]);
    }

    private function buildCatalogRows(array $entries, PartCatalogEntryRepository $repository, string $sort, string $dir, array $partTypeMap = []): array
    {
        $rows = array_map(function (PartCatalogEntry $entry) use ($repository, $partTypeMap) {
            $directParent = $entry->getCategory() ? $entry->getCategory()->getParent() : null;
            $partType = $partTypeMap[$entry->getCategory()?->getId()] ?? null;

            return [
                'entry' => $entry,
                'rootCategoryName' => $directParent ? $directParent->getName() : null,
                'sellerCount' => count($repository->getSellerUsageStats($entry->getId())),
                'productCount' => count($repository->getAttachedProducts($entry->getId())),
                'engineCount' => $entry->getEngineCompatibilities()->count(),
                'totalQuantity' => $repository->getTotalQuantity($entry->getId()),
                'partType' => $partType,
                'partTypeLabel' => 'moto' === $partType ? 'Moto' : ('auto' === $partType ? 'Auto' : null),
            ];
        }, $entries);

        $allowed = ['name', 'rootCategoryName', 'subcategory', 'brand', 'partType', 'sellerCount', 'engineCount', 'productCount', 'totalQuantity', 'status', 'updatedAt'];
        if (!in_array($sort, $allowed, true)) {
            $sort = 'name';
        }
        $dirMultiplier = strtoupper($dir) === 'DESC' ? -1 : 1;

        usort($rows, function ($a, $b) use ($sort, $dirMultiplier) {
            $valA = match ($sort) {
                'rootCategoryName' => $a['rootCategoryName'] ?? '',
                'subcategory' => $a['entry']->getCategory() ? $a['entry']->getCategory()->getName() : '',
                'brand' => $a['entry']->getBrand() ? $a['entry']->getBrand()->getName() : '',
                'partType' => $a['partTypeLabel'] ?? '',
                'sellerCount' => $a['sellerCount'],
                'engineCount' => $a['engineCount'],
                'productCount' => $a['productCount'],
                'totalQuantity' => $a['totalQuantity'],
                'status' => $a['entry']->getStatus(),
                'updatedAt' => $a['entry']->getUpdatedAt()?->getTimestamp() ?? 0,
                default => $a['entry']->getName(),
            };
            $valB = match ($sort) {
                'rootCategoryName' => $b['rootCategoryName'] ?? '',
                'subcategory' => $b['entry']->getCategory() ? $b['entry']->getCategory()->getName() : '',
                'brand' => $b['entry']->getBrand() ? $b['entry']->getBrand()->getName() : '',
                'partType' => $b['partTypeLabel'] ?? '',
                'sellerCount' => $b['sellerCount'],
                'engineCount' => $b['engineCount'],
                'productCount' => $b['productCount'],
                'totalQuantity' => $b['totalQuantity'],
                'status' => $b['entry']->getStatus(),
                'updatedAt' => $b['entry']->getUpdatedAt()?->getTimestamp() ?? 0,
                default => $b['entry']->getName(),
            };
            return $dirMultiplier * ($valA <=> $valB);
        });

        return $rows;
    }

    #[Route('/pieces-catalogue/nouveau', name: 'manage_part_catalog_new', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function new(CategoryRepository $categoryRepository, \App\Repository\BrandRepository $brandRepository): Response
    {
        $partBrands = array_values(array_filter(
            $brandRepository->findBy([], ['name' => 'ASC']),
            fn (\App\Entity\Brand $b) => $b->hasType('piece')
        ));

        return $this->render('manage/part_catalog/form.html.twig', [
            'entry' => null,
            'rootCategories' => $categoryRepository->findChildrenOf(null),
            'categoryVehiclePartMap' => $categoryRepository->findAllVehiclePartEligibleMap(),
            'categoryPartTypeMap' => $categoryRepository->findAllPartTypeMap(),
            'allBrands' => $partBrands,
            'vehicleBrandsGrouped' => $brandRepository->findVehicleBrandsGrouped(),
            'motoVehicleBrandsGrouped' => $brandRepository->findMotoVehicleBrandsGrouped(),
        ]);
    }

    #[Route('/pieces-catalogue/nouveau', name: 'manage_part_catalog_create', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $entry = new PartCatalogEntry();
        $this->hydrateBasics($entry, $request, $em);
        $em->persist($entry);
        $em->flush(); // il faut l'ID de l'entrée avant de pouvoir y rattacher OEM/caractéristiques/images

        $this->hydrateOemCodes($entry, $request, $em);
        $this->hydrateCharacteristicsFromRaw($entry, $request, $em);
        $this->hydrateImages($entry, $request, $em);
        $this->hydrateBrandCompatibilities($entry, $request, $em);
        $em->flush();

        $this->addFlash('success', 'Fiche catalogue créée.');
        return $this->redirectToRoute('manage_part_catalog_show', ['id' => $entry->getId()]);
    }

    #[Route('/pieces-catalogue/{id}/voir', name: 'manage_part_catalog_view', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function view(PartCatalogEntry $entry, \App\Repository\PartCatalogEntryRepository $repository): Response
    {
        $groupedVehicles = [];
        foreach ($entry->getEngineCompatibilities() as $compat) {
            $engine = $compat->getVehicleEngine();
            $brandKey = $engine->getBrandNameCache() ?: 'Autre';
            $modelVariantKey = trim(($engine->getModelNameCache() ?: '—') . ' ' . ($engine->getVariantNameCache() ?: 'Standard'));
            $fuelKey = $engine->getFuelType() ? $engine->getFuelType()->getName() : 'Énergie non renseignée';
            $groupedVehicles[$brandKey][$modelVariantKey][$fuelKey][] = $engine;
        }

        $similar = $repository->findSimilarByOem($entry);

        return $this->render('manage/part_catalog/view.html.twig', [
            'entry' => $entry,
            'groupedVehicles' => $groupedVehicles,
            'sellerStats' => $repository->getSellerUsageStats($entry->getId()),
            'attachedProducts' => $repository->getAttachedProducts($entry->getId()),
            'similarCatalogEntries' => $similar['catalogEntries'],
            'similarProducts' => $similar['products'],
        ]);
    }

    #[Route('/pieces-catalogue/{id}/bloquer', name: 'manage_part_catalog_toggle_block', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleBlock(PartCatalogEntry $entry, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $entry->setBlocked(!$entry->isBlocked());
        $em->flush();

        $this->addFlash('success', $entry->getName() . ($entry->isBlocked() ? ' bloquée.' : ' débloquée.'));

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('manage_part_catalog_index');
    }

    #[Route('/pieces-catalogue/{id}', name: 'manage_part_catalog_show', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        PartCatalogEntry $entry,
        CategoryRepository $categoryRepository,
        \App\Repository\BrandRepository $brandRepository
    ): Response {
        $partBrands = array_values(array_filter(
            $brandRepository->findBy([], ['name' => 'ASC']),
            fn (\App\Entity\Brand $b) => $b->hasType('piece')
        ));
        usort($partBrands, fn (\App\Entity\Brand $a, \App\Entity\Brand $b) => ($b->isPremium() <=> $a->isPremium()) ?: strcasecmp($a->getName(), $b->getName()));

        $ancestorIds = [];
        $node = $entry->getCategory();
        while ($node) {
            array_unshift($ancestorIds, $node->getId());
            $node = $node->getParent();
        }

        $characteristicsRaw = [];
        foreach ($entry->getAttributeValues() as $v) {
            $charac = $v->getCategoryAttribute()->getCharacteristic();
            $label = $charac->getName() . ($charac->getUnit() ? ' [' . $charac->getUnit() . ']' : '');
            $characteristicsRaw[] = $label . ':' . $v->getTextValue();
        }

        $groupedVehicles = [];
        foreach ($entry->getEngineCompatibilities() as $compat) {
            $engine = $compat->getVehicleEngine();
            $brandKey = $engine->getBrandNameCache() ?: 'Autre';
            // Les Moto n'ont pas de variante — on l'omet simplement du libellé plutôt qu'un faux "Standard".
            $modelVariantKey = trim(($engine->getModelNameCache() ?: '—') . ' ' . ($engine->getVariantNameCache() ?: ''));
            $fuelKey = $engine->getFuelType() ? $engine->getFuelType()->getName() : 'Énergie non renseignée';
            $groupedVehicles[$brandKey][$modelVariantKey][$fuelKey][] = $engine;
        }

        return $this->render('manage/part_catalog/show.html.twig', [
            'entry' => $entry,
            'rootCategories' => $categoryRepository->findChildrenOf(null),
            'categoryVehiclePartMap' => $categoryRepository->findAllVehiclePartEligibleMap(),
            'categoryPartTypeMap' => $categoryRepository->findAllPartTypeMap(),
            'entryPartType' => $categoryRepository->findAllPartTypeMap()[$entry->getCategory()?->getId()] ?? null,
            'categoryAncestorIds' => implode(',', $ancestorIds),
            'allBrands' => $partBrands,
            'vehicleBrandsGrouped' => $brandRepository->findVehicleBrandsGrouped(),
            'characteristicsRawPrefill' => implode("\n", $characteristicsRaw),
            'groupedVehicles' => $groupedVehicles,
        ]);
    }

    #[Route('/pieces-catalogue/{id}/modifier', name: 'manage_part_catalog_update', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(PartCatalogEntry $entry, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $entry->setUpdatedAt(new \DateTimeImmutable());
        $this->hydrateBasics($entry, $request, $em);
        $this->hydrateOemCodes($entry, $request, $em);
        $this->hydrateBrandCompatibilities($entry, $request, $em);
        $this->hydrateCharacteristicsFromRaw($entry, $request, $em);
        $this->hydrateImages($entry, $request, $em);
        $em->flush();

        $this->addFlash('success', 'Fiche catalogue mise à jour.');
        return $this->redirectToRoute('manage_part_catalog_show', ['id' => $entry->getId()]);
    }

    #[Route('/pieces-catalogue/{id}/valider', name: 'manage_part_catalog_validate', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function validate(PartCatalogEntry $entry, EntityManagerInterface $em): RedirectResponse
    {
        $entry->setStatus('validated' === $entry->getStatus() ? 'pending_review' : 'validated');
        $em->flush();

        $this->addFlash('success', 'validated' === $entry->getStatus() ? 'Fiche validée.' : 'Fiche repassée en attente.');
        return $this->redirectToRoute('manage_part_catalog_show', ['id' => $entry->getId()]);
    }

    #[Route('/pieces-catalogue/{id}/supprimer', name: 'manage_part_catalog_delete', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(PartCatalogEntry $entry, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($entry);
        $em->flush();

        $this->addFlash('success', 'Fiche catalogue supprimée.');
        return $this->redirectToRoute('manage_part_catalog_index');
    }

    private function hydrateBasics(PartCatalogEntry $entry, Request $request, EntityManagerInterface $em): void
    {
        $entry->setVerified((bool) $request->request->get('is_verified'));
        $entry->setComplete((bool) $request->request->get('is_complete'));

        $status = $request->request->get('status');
        $entry->setStatus(in_array($status, ['draft', 'pending_review', 'validated'], true) ? $status : 'draft');

        $categoryId = $request->request->get('category_id') ? (int) $request->request->get('category_id') : null;
        $category = $categoryId ? $em->getRepository(Category::class)->find($categoryId) : null;
        $entry->setCategory($category);

        $brandId = $request->request->get('brand_id') ? (int) $request->request->get('brand_id') : null;
        $brand = $brandId ? $em->getRepository(Brand::class)->find($brandId) : null;
        $entry->setBrand($brand);

        $entry->setEan($request->request->get('ean') ?: null);
        $manufacturerRef = $request->request->get('manufacturer_ref') ?: null;
        $entry->setManufacturerRef($manufacturerRef);
        $entry->setDescription($request->request->get('description') ?: null);
        $entry->setNote($request->request->get('note') ?: null);

        $name = trim((string) $request->request->get('name'));
        if ('' === $name) {
            $name = trim(implode(' ', array_filter([
                $category?->getName(),
                $brand?->getName(),
                $manufacturerRef,
            ])));
        }
        $entry->setName($name);
    }

    private function hydrateImages(PartCatalogEntry $entry, Request $request, EntityManagerInterface $em): void
    {
        $orderTokens = array_filter(explode(',', (string) $request->request->get('image_order', '')));
        $newFiles = $request->files->all('images') ?: [];
        $newFileIndex = 0;
        $position = 0;

        // Supprime les images existantes cochées pour suppression (utile en édition, sans effet à la création)
        $removeIds = $request->request->all('remove_image_ids');
        if ($removeIds) {
            foreach ($entry->getImages() as $img) {
                if (in_array((string) $img->getId(), $removeIds, true)) {
                    $em->remove($img);
                }
            }
        }

        if ($orderTokens) {
            foreach ($orderTokens as $token) {
                if (str_starts_with($token, 'existing:')) {
                    $id = (int) substr($token, 9);
                    foreach ($entry->getImages() as $img) {
                        if ($img->getId() === $id) {
                            $img->setPosition($position);
                        }
                    }
                } elseif ('new' === $token && isset($newFiles[$newFileIndex])) {
                    $image = new \App\Entity\PartCatalogImage();
                    $image->setPartCatalogEntry($entry);
                    $image->setImageFile($newFiles[$newFileIndex]);
                    $image->setPosition($position);
                    $em->persist($image);
                    $newFileIndex++;
                }
                $position++;
            }
        } else {
            // Pas d'ordre transmis (ex: tout premier envoi) — on garde simplement l'ordre d'upload.
            foreach ($newFiles as $file) {
                $image = new \App\Entity\PartCatalogImage();
                $image->setPartCatalogEntry($entry);
                $image->setImageFile($file);
                $image->setPosition($position);
                $em->persist($image);
                $position++;
            }
        }
    }

    private function hydrateOemCodes(PartCatalogEntry $entry, Request $request, EntityManagerInterface $em): void
    {
        foreach ($entry->getOemCodes() as $existing) {
            $entry->removeOemCode($existing);
            $em->remove($existing);
        }

        $raw = (string) $request->request->get('oem_codes', '');
        $lines = preg_split('/\r\n|\r|\n/', trim($raw));

        foreach ($lines as $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }

            $code = $line;
            $brandNames = [null]; // pas de marque précisée

            // "OE" pour l'Auto, "OEN" pour la Moto — on reconnaît les deux.
            if (preg_match('/^(?:OEN?\s+)?(.+?)\s*[—–]\s*(.+)$/u', $line, $m)) {
                $code = trim($m[1]);
                // "HYUNDAI / KIA" — un même OEM partagé par plusieurs marques → une ligne par marque.
                $brandNames = array_filter(array_map('trim', explode('/', $m[2])));
            } else {
                $code = trim((string) preg_replace('/^OEN?\s+/i', '', $line));
            }

            if ('' === $code) {
                continue;
            }

            foreach ($brandNames as $brandName) {
                $brand = $brandName ? $em->getRepository(Brand::class)->findOneBy(['name' => $brandName]) : null;

                $oem = new PartCatalogOemCode();
                $oem->setCode($code);
                $oem->setBrand($brand);
                $entry->addOemCode($oem);
                $em->persist($oem);
            }
        }
    }
    /**
     * Parseur du texte brut "Caractéristiques" au format "Label:Valeur" ou "Label [unité]:Valeur".
     * Retrouve ou crée automatiquement la Characteristic + son rattachement à la catégorie (CategoryAttribute).
     */
    private function hydrateCharacteristicsFromRaw(PartCatalogEntry $entry, Request $request, EntityManagerInterface $em): void
    {
        $category = $entry->getCategory();
        if (!$category) {
            return;
        }

        $raw = (string) $request->request->get('characteristics_raw', '');
        $lines = preg_split('/\r\n|\r|\n/', trim($raw));

        $existingValues = [];
        foreach ($em->getRepository(PartCatalogAttributeValue::class)->findBy(['partCatalogEntry' => $entry]) as $v) {
            $existingValues[$v->getCategoryAttribute()->getId()] = $v;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ('' === $line || !str_contains($line, ':')) {
                continue;
            }

            [$left, $value] = array_pad(explode(':', $line, 2), 2, null);
            $value = trim((string) $value);
            if ('' === $value) {
                continue;
            }

            $unit = null;
            $label = trim($left);
            if (preg_match('/^(.*)\[([^\]]+)\]\s*$/u', $label, $m)) {
                $label = trim($m[1]);
                $unit = trim($m[2]);
            }
            if ('' === $label) {
                continue;
            }

            $characteristic = $em->getRepository(Characteristic::class)->createQueryBuilder('c')
                ->andWhere('LOWER(c.name) = :name')
                ->setParameter('name', mb_strtolower($label))
                ->getQuery()
                ->getOneOrNullResult();

            if (!$characteristic) {
                $characteristic = new Characteristic();
                $characteristic->setName($label);
                $characteristic->setUnit($unit);
                $em->persist($characteristic);
            }

            $categoryAttribute = $em->getRepository(CategoryAttribute::class)->findOneBy([
                'category' => $category,
                'characteristic' => $characteristic,
            ]);

            if (!$categoryAttribute) {
                $categoryAttribute = new CategoryAttribute();
                $categoryAttribute->setCategory($category);
                $categoryAttribute->setCharacteristic($characteristic);
                $categoryAttribute->setPosition(0);
                $categoryAttribute->setNullable(true);
                $categoryAttribute->setFilterable(false);
                $categoryAttribute->setShowOnCard(false);
                $em->persist($categoryAttribute);
                $em->flush(); // pour avoir un ID avant de l'utiliser comme clé ci-dessous
            }

            $attrValue = $existingValues[$categoryAttribute->getId()] ?? new PartCatalogAttributeValue();
            $attrValue->setPartCatalogEntry($entry);
            $attrValue->setCategoryAttribute($categoryAttribute);
            $attrValue->setTextValue($value);
            $em->persist($attrValue);
        }
    }

    private function hydrateBrandCompatibilities(PartCatalogEntry $entry, Request $request, EntityManagerInterface $em): void
    {
        foreach ($entry->getBrandCompatibilities() as $existing) {
            $entry->removeBrandCompatibility($existing);
            $em->remove($existing);
        }

        // Auto et Moto utilisent 2 selects distincts côté formulaire (un seul des deux est actif à la fois) — on fusionne les deux.
        $brandIds = array_merge(
            $request->request->all('compatible_brand_ids'),
            $request->request->all('compatible_brand_ids_moto')
        );

        foreach (array_unique($brandIds) as $brandId) {
            $brand = $em->getRepository(Brand::class)->find((int) $brandId);
            if ($brand) {
                $compat = new PartCatalogBrandCompatibility();
                $compat->setBrand($brand);
                $entry->addBrandCompatibility($compat);
                $em->persist($compat);
            }
        }
    }

    private function hydrateAttributes(PartCatalogEntry $entry, Request $request, EntityManagerInterface $em): void
    {
        $existing = [];
        foreach ($em->getRepository(PartCatalogAttributeValue::class)->findBy(['partCatalogEntry' => $entry]) as $v) {
            $existing[$v->getCategoryAttribute()->getId()] = $v;
        }

        foreach ($request->request->all('attr') as $attrId => $raw) {
            $attrId = (int) $attrId;

            if ('' === trim((string) $raw)) {
                if (isset($existing[$attrId])) {
                    $em->remove($existing[$attrId]);
                    unset($existing[$attrId]);
                }
                continue;
            }

            $categoryAttribute = $em->getRepository(CategoryAttribute::class)->find($attrId);
            if (!$categoryAttribute) {
                continue;
            }

            $value = $existing[$attrId] ?? new PartCatalogAttributeValue();
            $value->setPartCatalogEntry($entry);
            $value->setCategoryAttribute($categoryAttribute);
            $value->setTextValue(null);
            $value->setNumberValue(null);
            $value->setBooleanValue(null);
            $value->setCategoryAttributeOption(null);

            match ($categoryAttribute->getDataType()) {
                'number' => $value->setNumberValue((string) $raw),
                'boolean' => $value->setBooleanValue('1' === $raw),
                'select' => $value->setCategoryAttributeOption($em->getRepository(\App\Entity\CharacteristicOption::class)->find((int) $raw)),
                default => $value->setTextValue((string) $raw),
            };

            $em->persist($value);
        }
    }
}