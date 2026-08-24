<?php

namespace App\Controller\Manage;

use App\Entity\Brand;
use App\Entity\PartCatalogEngineCompatibility;
use App\Entity\PartCatalogEntry;
use App\Entity\Product;
use App\Entity\VehicleEngine;
use App\Entity\VehicleModel;
use App\Entity\VehicleVariant;
use App\Repository\VehicleEngineRepository;
use App\Service\PartCatalogVehicleAssociationParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class PartCatalogVehicleAssociationController extends AbstractController
{
    #[Route('/pieces-catalogue/moto-cascade/marques', name: 'manage_part_catalog_moto_brands', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function motoCascadeBrands(\App\Repository\BrandRepository $brandRepository): JsonResponse
    {
        $brands = $brandRepository->findMotoVehicleBrands();

        return new JsonResponse(['results' => array_map(fn ($b) => ['id' => $b->getId(), 'name' => $b->getName()], $brands)]);
    }

    #[Route('/pieces-catalogue/moto-cascade/modeles/{brandId}', name: 'manage_part_catalog_moto_models', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['brandId' => '\d+'])]
    public function motoCascadeModels(int $brandId, \App\Repository\VehicleModelRepository $modelRepository): JsonResponse
    {
        $models = $modelRepository->findByBrandAndType($brandId, true);

        return new JsonResponse(['results' => array_map(fn ($m) => ['id' => $m->getId(), 'name' => $m->getName()], $models)]);
    }

    #[Route('/pieces-catalogue/moto-cascade/motorisations/{modelId}', name: 'manage_part_catalog_moto_engines', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['modelId' => '\d+'])]
    public function motoCascadeEngines(int $modelId, \App\Repository\VehicleEngineRepository $engineRepository): JsonResponse
    {
        $engines = $engineRepository->findByModel($modelId);

        $results = array_map(fn ($e) => [
            'id' => $e->getId(),
            'label' => trim($e->getLabel() . ' ' . ($e->getPeriodLabel() ? '(' . $e->getPeriodLabel() . ')' : '')),
        ], $engines);

        return new JsonResponse(['results' => $results]);
    }

    #[Route('/pieces-catalogue/vehicules-associes/rechercher-source', name: 'manage_part_catalog_va_search_source', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function searchSource(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $term = trim((string) $request->query->get('q', ''));
        $excludeId = (int) $request->query->get('exclude', 0);
        $termLike = '%' . $term . '%';

        // Recherche catalogue : nom, EAN, réf. fabricant.
        $catalogQb = $em->getRepository(PartCatalogEntry::class)->createQueryBuilder('e')
            ->andWhere('e.name LIKE :term OR e.ean LIKE :term OR e.manufacturerRef LIKE :term')
            ->setParameter('term', $termLike)
            ->setMaxResults(10);
        $catalogEntries = $catalogQb->getQuery()->getResult();
        if ($excludeId) {
            $catalogEntries = array_filter($catalogEntries, fn ($e) => $e->getId() !== $excludeId);
        }

        // Recherche produits classiques : titre, EAN, réf. fabricant (générique + spécifique pièce), et réf. KongoBazar (KBZ-000042 ou juste l'ID numérique).
        $productQb = $em->getRepository(\App\Entity\Product::class)->createQueryBuilder('p')
            ->join('p.partListingDetails', 'pld')
            ->setMaxResults(10);

        $conditions = $productQb->expr()->orX(
            $productQb->expr()->like('p.title', ':term'),
            $productQb->expr()->like('p.ean', ':term'),
            $productQb->expr()->like('p.reference', ':term'),
            $productQb->expr()->like('pld.ean', ':term'),
            $productQb->expr()->like('pld.manufacturerRef', ':term')
        );
        $productQb->setParameter('term', $termLike);

        if (preg_match('/(\d{1,10})/', $term, $m)) {
            $numericId = (int) ltrim($m[1], '0');
            if ($numericId > 0) {
                $conditions->add($productQb->expr()->eq('p.id', ':termId'));
                $productQb->setParameter('termId', $numericId);
            }
        }

        $products = $productQb->andWhere($conditions)->getQuery()->getResult();

        $results = [];
        foreach ($catalogEntries as $e) {
            $results[] = ['type' => 'catalog', 'id' => $e->getId(), 'label' => $e->getName() . ' (catalogue)'];
        }
        foreach ($products as $p) {
            $results[] = ['type' => 'product', 'id' => $p->getId(), 'label' => $p->getTitle() . ' — ' . $p->getKongobazarReference() . ' (produit)'];
        }

        return new JsonResponse(['results' => $results]);
    }
    #[Route('/pieces-catalogue/vehicules-associes/source/{type}/{id}', name: 'manage_part_catalog_va_source_engines', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+', 'type' => 'catalog|product'])]
    public function sourceEngines(string $type, int $id, EntityManagerInterface $em): JsonResponse
    {
        if ('catalog' === $type) {
            $entry = $em->getRepository(PartCatalogEntry::class)->find($id);
            $compatibilities = $entry ? $entry->getEngineCompatibilities()->toArray() : [];
        } else {
            $product = $em->getRepository(\App\Entity\Product::class)->find($id);
            $compatibilities = ($product && $product->getPartListingDetails())
                ? $product->getPartListingDetails()->getEngineCompatibilities()->toArray()
                : [];
        }

        $items = array_map(fn ($compat) => [
            'engineId' => $compat->getVehicleEngine()->getId(),
            'label' => trim($compat->getVehicleEngine()->getBrandNameCache() . ' ' . $compat->getVehicleEngine()->getModelNameCache() . ' ' . $compat->getVehicleEngine()->getVariantNameCache() . ' ' . $compat->getVehicleEngine()->getLabel()),
        ], $compatibilities);

        return new JsonResponse(['items' => $items]);
    }

    #[Route('/pieces-catalogue/vehicules-associes/analyser', name: 'manage_part_catalog_va_analyze', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function analyze(Request $request, PartCatalogVehicleAssociationParser $parser): JsonResponse
    {
        $raw = (string) $request->request->get('text', '');
        if ('' === trim($raw)) {
            return new JsonResponse(['error' => 'Colle du texte à analyser.']);
        }

        $groups = $parser->parse($raw);

        $out = array_map(function (array $g) {
            return [
                'headerLine' => $g['headerLine'],
                'brand' => ['found' => $g['brand']['found'], 'id' => $g['brand']['id'], 'triedText' => $g['brand']['triedText'], 'inputPrefix' => $g['brand']['inputPrefix']],
                'model' => ['found' => $g['model']['found'], 'id' => $g['model']['id'], 'triedText' => $g['model']['triedText'], 'inputText' => $g['model']['inputText']],
                'variant' => ['found' => $g['variant']['found'], 'id' => $g['variant']['id'], 'inputText' => $g['variant']['inputText']],
                'periodBegin' => $g['periodBegin'],
                'periodEnd' => $g['periodEnd'],
                'engines' => array_map(fn ($e) => [
                    'raw' => $e['raw'],
                    'label' => $e['label'],
                    'periodBegin' => $e['periodBegin'],
                    'periodEnd' => $e['periodEnd'],
                    'displacementCc' => $e['displacementCc'],
                    'powerCv' => $e['powerCv'],
                    'exists' => $e['exists'],
                    'existingId' => $e['existingId'] ?? null,
                ], $g['engines']),
                'unrecognizedLines' => $g['unrecognizedLines'],
            ];
        }, $groups);

        return new JsonResponse(['groups' => $out]);
    }

    #[Route('/pieces-catalogue/oem/verifier-marques', name: 'manage_part_catalog_oem_check_brands', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function checkOemBrands(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $raw = (string) $request->request->get('text', '');
        $lines = preg_split('/\r\n|\r|\n/', trim($raw));

        $names = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }
            if (preg_match('/^(?:OEN?\s+)?(.+?)\s*[—–]\s*(.+)$/u', $line, $m)) {
                foreach (explode('/', $m[2]) as $name) {
                    $name = trim($name);
                    if ('' !== $name) {
                        $names[$name] = true;
                    }
                }
            }
        }

        $result = [];
        foreach (array_keys($names) as $name) {
            $brand = $em->getRepository(Brand::class)->createQueryBuilder('b')
                ->andWhere('LOWER(b.name) = :name')
                ->setParameter('name', mb_strtolower($name))
                ->getQuery()
                ->getOneOrNullResult();

            $result[] = [
                'name' => $name,
                'found' => null !== $brand,
                'id' => $brand?->getId(),
                // Trouvée mais pas une marque de véhicule (ex: Bosch) — pas une erreur, juste hors-sujet pour le select "véhicules compatibles".
                'isVehicleBrand' => $brand ? ($brand->hasType('auto') || $brand->hasType('moto')) : false,
            ];
        }

        return new JsonResponse(['brands' => $result]);
    }

    #[Route('/pieces-catalogue/vehicules-associes/creer-marque', name: 'manage_part_catalog_va_create_brand', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function createBrand(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $name = trim((string) $request->request->get('name'));
        if ('' === $name) {
            return new JsonResponse(['error' => 'Nom requis.'], 400);
        }

        // Par défaut "oui" (flux Véhicules associés, toujours une marque véhicule sans ambiguïté).
        // Le flux OEM envoie explicitement 0/1 selon le choix de l'utilisateur.
        $isAuto = $request->request->get('is_auto', '1') === '1';

        $slugger = new \Symfony\Component\String\Slugger\AsciiSlugger();

        $brand = new Brand();
        $brand->setName($name);
        $brand->setSlug(strtolower($slugger->slug($name)) . '-' . uniqid());
        $brand->setType($isAuto ? ['auto'] : []);
        $brand->setActive(true);
        $brand->setVerified(false);
        $em->persist($brand);
        $em->flush();

        return new JsonResponse(['id' => $brand->getId(), 'name' => $brand->getName(), 'isVehicleBrand' => $isAuto]);
    }

    #[Route('/pieces-catalogue/vehicules-associes/creer-modele', name: 'manage_part_catalog_va_create_model', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function createModel(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $brandId = (int) $request->request->get('brand_id');
        $name = trim((string) $request->request->get('name'));
        $brand = $em->getRepository(Brand::class)->find($brandId);
        if (!$brand || '' === $name) {
            return new JsonResponse(['error' => 'Marque ou nom invalide.'], 400);
        }

        $model = new VehicleModel();
        $model->setBrand($brand);
        $model->setName($name);
        $em->persist($model);
        $em->flush();

        return new JsonResponse(['id' => $model->getId(), 'name' => $model->getName()]);
    }

    #[Route('/pieces-catalogue/vehicules-associes/creer-variante', name: 'manage_part_catalog_va_create_variant', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function createVariant(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $modelId = (int) $request->request->get('model_id');
        $name = trim((string) $request->request->get('name'));
        $model = $em->getRepository(VehicleModel::class)->find($modelId);
        if (!$model) {
            return new JsonResponse(['error' => 'Modèle invalide.'], 400);
        }

        $variant = new VehicleVariant();
        $variant->setModel($model);
        $variant->setName($name ?: null);
        $variant->setMonthBegin((string) $request->request->get('month_begin', '01'));
        $variant->setYearBegin((int) $request->request->get('year_begin'));
        $variant->setMonthEnd($request->request->get('month_end') ?: null);
        $variant->setYearEnd($request->request->get('year_end') ? (int) $request->request->get('year_end') : null);
        $em->persist($variant);
        $em->flush();

        return new JsonResponse(['id' => $variant->getId(), 'name' => $variant->getName()]);
    }

    /** Crée (si besoin) les motorisations manquantes d'une variante déjà résolue, puis les rattache à la fiche catalogue. */
    #[Route('/pieces-catalogue/{id}/vehicules-associes/enregistrer-groupe', name: 'manage_part_catalog_va_save_group', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function saveGroup(PartCatalogEntry $entry, Request $request, EntityManagerInterface $em, VehicleEngineRepository $engineRepository): JsonResponse
    {
        $variantId = (int) $request->request->get('variant_id');
        $variant = $em->getRepository(VehicleVariant::class)->find($variantId);
        if (!$variant) {
            return new JsonResponse(['error' => 'Variante invalide.'], 400);
        }

        $engines = json_decode((string) $request->request->get('engines', '[]'), true) ?: [];
        $attached = 0;

        foreach ($engines as $row) {
            $engineId = $row['existingId'] ?? null;
            $engine = $engineId ? $em->getRepository(VehicleEngine::class)->find($engineId) : null;

            if (!$engine) {
                $engine = new VehicleEngine();
                $engine->setVariant($variant);
                $engine->setLabel($row['label']);
                $engine->setPowerCv((int) $row['powerCv']);
                $engine->setPowerKw((int) round($row['powerCv'] * 0.7355));
                $engine->setDisplacementCc((int) $row['displacementCc']);
                $engine->setMonthStart($row['periodBegin']['month']);
                $engine->setYearStart((int) $row['periodBegin']['year']);
                $engine->setMonthEnd($row['periodEnd']['month'] ?? null);
                $engine->setYearEnd(isset($row['periodEnd']['year']) ? (int) $row['periodEnd']['year'] : null);

                $brand = $variant->getModel()->getBrand();
                $engine->setBrandNameCache($brand?->getName());
                $engine->setModelNameCache($variant->getModel()->getName());
                $engine->setVariantNameCache($variant->getName());
                $periodEnd = $row['periodEnd'] ? $row['periodEnd']['month'] . '.' . $row['periodEnd']['year'] : '...';
                $engine->setPeriodLabel($row['periodBegin']['month'] . '.' . $row['periodBegin']['year'] . ' - ' . $periodEnd);
                $engine->setUpdatedAt(new \DateTimeImmutable());

                $em->persist($engine);
                $em->flush();
            }

            $alreadyAttached = false;
            foreach ($entry->getEngineCompatibilities() as $existing) {
                if ($existing->getVehicleEngine()->getId() === $engine->getId()) {
                    $alreadyAttached = true;
                    break;
                }
            }

            if (!$alreadyAttached) {
                $compat = new PartCatalogEngineCompatibility();
                $compat->setVehicleEngine($engine);
                $entry->addEngineCompatibility($compat);
                $em->persist($compat);
                $attached++;
            }
        }

        $em->flush();

        return new JsonResponse(['attached' => $attached]);
    }
}