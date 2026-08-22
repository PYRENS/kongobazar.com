<?php

namespace App\Controller\Manage;

use App\Entity\Brand;
use App\Entity\FuelType;
use App\Entity\VehicleEngine;
use App\Entity\VehicleModel;
use App\Entity\VehicleVariant;
use App\Repository\BrandRepository;
use App\Repository\FuelTypeRepository;
use App\Repository\VehicleEngineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VehicleEngineManagementController extends AbstractController
{
    #[Route('/vehicules/motorisations', name: 'manage_vehicle_engines_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(Request $request, VehicleEngineRepository $repository): Response
    {
        $searchTerm = $request->query->get('q');
        $engines = $repository->findFiltered($searchTerm);

        $sortField = $request->query->get('sort', 'brand');
        $sortDir = $request->query->get('dir', 'ASC');
        $engines = $this->sortEngineList($engines, $sortField, $sortDir);

        $total = count($engines);
        $perPage = in_array((int) $request->query->get('perPage', 20), [10, 20, 50, 100], true)
            ? (int) $request->query->get('perPage', 20) : 20;
        $page = max(1, (int) $request->query->get('page', 1));
        $engines = array_slice($engines, ($page - 1) * $perPage, $perPage);

        return $this->render('manage/vehicle_engines/index.html.twig', [
            'engines' => $engines,
            'searchTerm' => $searchTerm,
            'currentSort' => $sortField,
            'currentDir' => $sortDir,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'perPage' => $perPage,
            'total' => $total,
            'stats' => [
                'total' => $repository->countAll(),
                'auto' => $repository->countAutoAll(),
                'moto' => $repository->countMotoAll(),
                'filtered' => $total,
            ],
        ]);
    }

    private function sortEngineList(array $engines, string $field, string $dir): array
    {
        $allowed = ['brand', 'label', 'power', 'fuelType', 'period'];
        if (!in_array($field, $allowed, true)) {
            $field = 'brand';
        }
        $dirMultiplier = strtoupper($dir) === 'DESC' ? -1 : 1;

        usort($engines, function ($a, $b) use ($field, $dirMultiplier) {
            $valA = match ($field) {
                'power' => $a->getPowerCv() ?? -1,
                'fuelType' => $a->getFuelType()?->getName() ?? '',
                'period' => $a->getYearStart() ?? 0,
                'label' => $a->getLabel(),
                default => trim(($a->getBrandNameCache() ?? '') . ' ' . ($a->getModelNameCache() ?? '') . ' ' . ($a->getVariantNameCache() ?? '')),
            };
            $valB = match ($field) {
                'power' => $b->getPowerCv() ?? -1,
                'fuelType' => $b->getFuelType()?->getName() ?? '',
                'period' => $b->getYearStart() ?? 0,
                'label' => $b->getLabel(),
                default => trim(($b->getBrandNameCache() ?? '') . ' ' . ($b->getModelNameCache() ?? '') . ' ' . ($b->getVariantNameCache() ?? '')),
            };
            return $dirMultiplier * ($valA <=> $valB);
        });

        return $engines;
    }

    #[Route('/vehicules/motorisations/nouveau', name: 'manage_vehicle_engines_new', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function new(Request $request, BrandRepository $brandRepository, FuelTypeRepository $fuelTypeRepository, EntityManagerInterface $em): Response
    {
        $presetVariantId = $request->query->get('variant') ? (int) $request->query->get('variant') : null;
        $presetVariant = $presetVariantId ? $em->getRepository(VehicleVariant::class)->find($presetVariantId) : null;

        return $this->render('manage/vehicle_engines/form.html.twig', [
            'engine' => null,
            'brands' => $brandRepository->findBy([], ['name' => 'ASC']),
            'fuelTypes' => $fuelTypeRepository->findAllActive(),
            'presetVariant' => $presetVariant,
        ]);
    }

    #[Route('/vehicules/motorisations/nouveau', name: 'manage_vehicle_engines_create', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $engine = new VehicleEngine();
        $error = $this->hydrate($engine, $request, $em);

        if (null !== $error) {
            $this->addFlash('error', $error);
            return $this->redirectToRoute('manage_vehicle_engines_new');
        }

        $em->persist($engine);
        $em->flush();

        $this->addFlash('success', 'Motorisation créée.');
        return $this->redirectToRoute('manage_vehicle_engines_index');
    }

    #[Route('/vehicules/motorisations/{id}/modifier', name: 'manage_vehicle_engines_edit', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function edit(VehicleEngine $engine, BrandRepository $brandRepository, FuelTypeRepository $fuelTypeRepository): Response
    {
        return $this->render('manage/vehicle_engines/form.html.twig', [
            'engine' => $engine,
            'brands' => $brandRepository->findBy([], ['name' => 'ASC']),
            'fuelTypes' => $fuelTypeRepository->findAllActive(),
            'presetVariant' => null,
        ]);
    }

    #[Route('/vehicules/motorisations/{id}/modifier', name: 'manage_vehicle_engines_update', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function update(VehicleEngine $engine, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $error = $this->hydrate($engine, $request, $em);

        if (null !== $error) {
            $this->addFlash('error', $error);
            return $this->redirectToRoute('manage_vehicle_engines_edit', ['id' => $engine->getId()]);
        }

        $em->flush();

        $this->addFlash('success', 'Motorisation mise à jour.');
        return $this->redirectToRoute('manage_vehicle_engines_index');
    }

    #[Route('/vehicules/motorisations/{id}/supprimer', name: 'manage_vehicle_engines_delete', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function delete(VehicleEngine $engine, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($engine);
        $em->flush();

        $this->addFlash('success', 'Motorisation supprimée.');
        return $this->redirectToRoute('manage_vehicle_engines_index');
    }

    #[Route('/vehicules/motorisations/{id}/pieces', name: 'manage_vehicle_engine_parts', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function parts(
        VehicleEngine $engine,
        Request $request,
        \App\Repository\ProductRepository $productRepository,
        \App\Repository\CategoryRepository $categoryRepository,
        \App\Repository\AdministrativeUnitRepository $administrativeUnitRepository,
        BrandRepository $brandRepository,
    ): Response {
        $term = $request->query->get('q') ?: null;
        $categoryId = $request->query->get('category') ? (int) $request->query->get('category') : null;
        $brandId = $request->query->get('brand') ? (int) $request->query->get('brand') : null;
        $condition = $request->query->get('condition') ?: null;
        $sort = $request->query->get('sort', 'createdAt');
        $dir = $request->query->get('dir', 'DESC');
        $perPage = in_array((int) $request->query->get('perPage', 20), [10, 20, 50, 100], true)
            ? (int) $request->query->get('perPage', 20) : 20;
        $page = max(1, (int) $request->query->get('page', 1));

        // Localisation : le vendeur/produit peut être situé à n'importe quel niveau
        // (province seule, ou jusqu'à la commune) — on filtre sur l'unité choisie + tous ses descendants.
        $locationUnitId = $request->query->get('location') ? (int) $request->query->get('location') : null;
        $locationUnitIds = null;
        $locationUnit = null;
        if ($locationUnitId) {
            $locationUnit = $administrativeUnitRepository->find($locationUnitId);
            if ($locationUnit) {
                $locationUnitIds = array_merge(
                    [$locationUnit->getId()],
                    array_map(fn ($u) => $u->getId(), $locationUnit->getDescendantUnits())
                );
            }
        }

        $total = $productRepository->countCompatibleWithEngine($engine->getId(), $term, $categoryId, $brandId, $condition, $locationUnitIds);
        $products = $productRepository->findCompatibleWithEngine($engine->getId(), $term, $categoryId, $brandId, $condition, $sort, $dir, $page, $perPage, $locationUnitIds);
        $categoryFacets = $productRepository->getCategoryFacetsForEngine($engine->getId(), $term, $brandId, $condition, $locationUnitIds);

        $grouped = [];
        foreach ($products as $product) {
            $catName = $product->getCategory() ? $product->getCategory()->getName() : 'Sans catégorie';
            $grouped[$catName][] = $product;
        }

        return $this->render('manage/vehicle_engines/parts.html.twig', [
            'engine' => $engine,
            'grouped' => $grouped,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'perPage' => $perPage,
            'currentTerm' => $term,
            'currentCategory' => $categoryId,
            'currentBrand' => $brandId,
            'currentCondition' => $condition,
            'currentSort' => $sort,
            'currentDir' => $dir,
            'currentLocationId' => $locationUnitId,
            'currentLocationUnit' => $locationUnit,
            'categories' => $categoryRepository->findBy([], ['name' => 'ASC']),
            'categoryFacets' => $categoryFacets,
            'brands' => $brandRepository->findBy([], ['name' => 'ASC']),
            'provinces' => $administrativeUnitRepository->findActiveRootUnits(),
            'allTitles' => $productRepository->findCompatibleWithEngineTitles($engine->getId()),
        ]);
    }

    /** @return string|null Message d'erreur si validation XOR échoue, sinon null. */
    private function hydrate(VehicleEngine $engine, Request $request, EntityManagerInterface $em): ?string
    {
        $type = (string) $request->request->get('vehicle_type'); // 'auto' | 'moto'
        $variantId = $request->request->get('variant_id') ? (int) $request->request->get('variant_id') : null;
        $modelId = $request->request->get('model_id') ? (int) $request->request->get('model_id') : null;

        if ('auto' === $type && !$variantId) {
            return 'Une Variante est requise pour une motorisation Auto.';
        }
        if ('moto' === $type && !$modelId) {
            return 'Un Modèle est requis pour une motorisation Moto.';
        }

        $variant = $variantId ? $em->getRepository(VehicleVariant::class)->find($variantId) : null;
        $model = $modelId ? $em->getRepository(VehicleModel::class)->find($modelId) : null;

        // XOR strict : jamais les deux en même temps
        $engine->setVariant('auto' === $type ? $variant : null);
        $engine->setModel('moto' === $type ? $model : null);

        $engine->setLabel((string) $request->request->get('label'));
        $engine->setPowerCv($request->request->get('power_cv') ? (int) $request->request->get('power_cv') : null);
        $engine->setPowerKw($request->request->get('power_kw') ? (int) $request->request->get('power_kw') : null);
        $engine->setDisplacementCc($request->request->get('displacement_cc') ? (int) $request->request->get('displacement_cc') : null);

        $fuelTypeId = $request->request->get('fuel_type_id') ? (int) $request->request->get('fuel_type_id') : null;
        $engine->setFuelType($fuelTypeId ? $em->getRepository(FuelType::class)->find($fuelTypeId) : null);

        $engine->setMonthStart($request->request->get('month_start') ?: null);
        $engine->setYearStart($request->request->get('year_start') ? (int) $request->request->get('year_start') : null);
        $engine->setMonthEnd($request->request->get('month_end') ?: null);
        $engine->setYearEnd($request->request->get('year_end') ? (int) $request->request->get('year_end') : null);

        // Caches dénormalisés — figent l'intitulé au moment de la saisie
        $brand = ('auto' === $type ? $variant?->getModel()?->getBrand() : $model?->getBrand());
        $engine->setBrandNameCache($brand?->getName());
        $engine->setModelNameCache('auto' === $type ? $variant?->getModel()?->getName() : $model?->getName());
        $engine->setVariantNameCache('auto' === $type ? $variant?->getName() : null);

        $periodStart = $engine->getMonthStart() && $engine->getYearStart()
            ? $engine->getMonthStart() . '.' . $engine->getYearStart()
            : null;
        $periodEnd = $engine->getMonthEnd() && $engine->getYearEnd()
            ? $engine->getMonthEnd() . '.' . $engine->getYearEnd()
            : '...';
        $engine->setPeriodLabel($periodStart ? $periodStart . ' - ' . $periodEnd : null);

        $engine->setUpdatedAt(new \DateTimeImmutable());

        return null;
    }
}