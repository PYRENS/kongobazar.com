<?php

namespace App\Controller\Manage;

use App\Entity\Brand;
use App\Entity\VehicleVariant;
use App\Repository\BrandRepository;
use App\Repository\VehicleModelRepository;
use App\Repository\VehicleVariantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VehicleVariantManagementController extends AbstractController
{
    #[Route('/vehicules/variantes', name: 'manage_vehicle_variants_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(
        Request $request,
        VehicleVariantRepository $repository,
        \App\Repository\VehicleEngineRepository $engineRepository
    ): Response {
        $searchTerm = $request->query->get('q');
        $variants = $repository->findFiltered($searchTerm);

        $rows = array_map(fn (\App\Entity\VehicleVariant $variant) => [
            'variant' => $variant,
            'engineCount' => $engineRepository->countByVariant($variant->getId()),
        ], $variants);

        $sortField = $request->query->get('sort', 'id');
        $sortDir = $request->query->get('dir', 'DESC');
        $rows = $this->sortRows($rows, $sortField, $sortDir);

        $total = count($rows);
        $perPage = in_array((int) $request->query->get('perPage', 20), [10, 20, 50, 100], true)
            ? (int) $request->query->get('perPage', 20) : 20;
        $page = max(1, (int) $request->query->get('page', 1));
        $rows = array_slice($rows, ($page - 1) * $perPage, $perPage);

        return $this->render('manage/vehicle_variants/index.html.twig', [
            'rows' => $rows,
            'searchTerm' => $searchTerm,
            'currentSort' => $sortField,
            'currentDir' => $sortDir,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'perPage' => $perPage,
            'total' => $total,
            'stats' => [
                'total' => $repository->countAll(),
                'totalEngines' => $engineRepository->countAll(),
                'filtered' => $total,
            ],
        ]);
    }

    #[Route('/vehicules/variantes/{id}', name: 'manage_vehicle_variants_show', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        VehicleVariant $variant,
        Request $request,
        \App\Repository\VehicleEngineRepository $engineRepository
    ): Response {
        return $this->render('manage/vehicle_variants/show.html.twig', array_merge(
            ['variant' => $variant],
            $this->buildEnginesData($variant, $request, $engineRepository)
        ));
    }

    #[Route('/vehicules/variantes/{id}/motorisations-fragment', name: 'manage_vehicle_variants_engines_fragment', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function enginesFragment(VehicleVariant $variant, Request $request, \App\Repository\VehicleEngineRepository $engineRepository): Response
    {
        return $this->render('manage/vehicle_variants/_engines_table.html.twig', array_merge(
            ['variant' => $variant],
            $this->buildEnginesData($variant, $request, $engineRepository)
        ));
    }

    private function buildEnginesData(VehicleVariant $variant, Request $request, \App\Repository\VehicleEngineRepository $engineRepository): array
    {
        $term = trim((string) $request->query->get('engines_q', ''));
        $sort = $request->query->get('engines_sort', 'label');
        $dir = $request->query->get('engines_dir', 'ASC');
        $page = max(1, (int) $request->query->get('engines_page', 1));
        $perPage = (int) $request->query->get('engines_perpage', 10);
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 10;

        $raw = $engineRepository->findByVariant($variant->getId());
        $allLabels = array_values(array_map(function ($e) {
            $period = $e->getPeriodLabel();
            $parts = array_filter([
                $e->getLabel(),
                $e->getFuelType() ? $e->getFuelType()->getName() : null,
                $e->getPowerCv() ? $e->getPowerCv() . ' Cv' : null,
            ]);
            $base = implode(' ', $parts);
            return [
                'value' => $e->getLabel(),
                'label' => $period ? $base . ' (' . $period . ')' : $base,
            ];
        }, $raw));

        $filtered = $term === ''
            ? $raw
            : array_values(array_filter($raw, fn ($e) => str_contains(mb_strtolower($e->getLabel() ?? ''), mb_strtolower($term))));

        $filtered = $this->sortEngines($filtered, $sort, $dir);
        $total = count($filtered);

        return [
            'engines' => array_slice($filtered, ($page - 1) * $perPage, $perPage),
            'enginesPage' => $page,
            'enginesPages' => max(1, (int) ceil($total / $perPage)),
            'enginesTotal' => $total,
            'enginesSort' => $sort,
            'enginesDir' => $dir,
            'enginesPerPage' => $perPage,
            'enginesTerm' => $term,
            'allEngineLabels' => $allLabels,
        ];
    }

    private function sortRows(array $rows, string $field, string $dir): array
    {
        $allowed = ['model', 'variant', 'brand', 'engineCount'];
        if (!in_array($field, $allowed, true)) {
            $field = 'variant';
        }
        $dirMultiplier = strtoupper($dir) === 'DESC' ? -1 : 1;

        usort($rows, function ($a, $b) use ($field, $dirMultiplier) {
            $valA = match ($field) {
                'model' => $a['variant']->getModel()->getName(),
                'brand' => $a['variant']->getModel()->getBrand()->getName(),
                'engineCount' => $a['engineCount'],
                default => $a['variant']->getName() ?? '',
            };
            $valB = match ($field) {
                'model' => $b['variant']->getModel()->getName(),
                'brand' => $b['variant']->getModel()->getBrand()->getName(),
                'engineCount' => $b['engineCount'],
                default => $b['variant']->getName() ?? '',
            };
            return $dirMultiplier * ($valA <=> $valB);
        });

        return $rows;
    }

    private function sortEngines(array $engines, string $field, string $dir): array
    {
        $allowed = ['label', 'powerCv', 'powerKw', 'period', 'fuelType'];
        if (!in_array($field, $allowed, true)) {
            $field = 'label';
        }
        $dirMultiplier = strtoupper($dir) === 'DESC' ? -1 : 1;

        usort($engines, function ($a, $b) use ($field, $dirMultiplier) {
            $valA = match ($field) {
                'powerCv' => $a->getPowerCv() ?? -1,
                // kW est désormais calculé depuis CV (kW = CV * 0,7355) — même ordre, donc on trie sur CV.
                'powerKw' => $a->getPowerCv() ?? -1,
                'period' => $a->getPeriodLabel() ?? '',
                'fuelType' => $a->getFuelType()?->getName() ?? '',
                default => $a->getLabel(),
            };
            $valB = match ($field) {
                'powerCv' => $b->getPowerCv() ?? -1,
                'powerKw' => $b->getPowerCv() ?? -1,
                'period' => $b->getPeriodLabel() ?? '',
                'fuelType' => $b->getFuelType()?->getName() ?? '',
                default => $b->getLabel(),
            };
            return $dirMultiplier * ($valA <=> $valB);
        });

        return $engines;
    }
    #[Route('/vehicules/variantes/nouveau', name: 'manage_vehicle_variants_new', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function new(Request $request, BrandRepository $brandRepository, VehicleModelRepository $modelRepository): Response
    {
        $presetModelId = $request->query->get('model') ? (int) $request->query->get('model') : null;
        $presetModel = $presetModelId ? $modelRepository->find($presetModelId) : null;

        return $this->render('manage/vehicle_variants/form.html.twig', [
            'variant' => null,
            'brands' => $brandRepository->findBy([], ['name' => 'ASC']),
            'presetModel' => $presetModel,
        ]);
    }

    #[Route('/vehicules/variantes/nouveau', name: 'manage_vehicle_variants_create', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $variant = new VehicleVariant();
        $this->hydrate($variant, $request, $em);
        $em->persist($variant);
        $em->flush();

        $this->addFlash('success', 'Variante créée.');
        return $this->redirectToRoute('manage_vehicle_variants_index');
    }

    #[Route('/vehicules/variantes/{id}/modifier', name: 'manage_vehicle_variants_edit', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function edit(VehicleVariant $variant, BrandRepository $brandRepository): Response
    {
        return $this->render('manage/vehicle_variants/form.html.twig', [
            'variant' => $variant,
            'brands' => $brandRepository->findBy([], ['name' => 'ASC']),
            'presetModel' => null,
        ]);
    }

    #[Route('/vehicules/variantes/{id}/modifier', name: 'manage_vehicle_variants_update', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function update(VehicleVariant $variant, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $this->hydrate($variant, $request, $em);
        $em->flush();

        $this->addFlash('success', 'Variante mise à jour.');
        return $this->redirectToRoute('manage_vehicle_variants_index');
    }

    #[Route('/vehicules/variantes/{id}/supprimer', name: 'manage_vehicle_variants_delete', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function delete(VehicleVariant $variant, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($variant);
        $em->flush();

        $this->addFlash('success', 'Variante supprimée.');
        return $this->redirectToRoute('manage_vehicle_variants_index');
    }

    private function hydrate(VehicleVariant $variant, Request $request, EntityManagerInterface $em): void
    {
        $variant->setName($request->request->get('name') ?: null);

        $modelId = (int) $request->request->get('model_id');
        $model = $em->getRepository(\App\Entity\VehicleModel::class)->find($modelId);
        $variant->setModel($model);

        $variant->setMonthBegin((string) $request->request->get('month_begin'));
        $variant->setYearBegin((int) $request->request->get('year_begin'));
        $variant->setMonthEnd($request->request->get('month_end') ?: null);
        $variant->setYearEnd($request->request->get('year_end') ? (int) $request->request->get('year_end') : null);
    }
}