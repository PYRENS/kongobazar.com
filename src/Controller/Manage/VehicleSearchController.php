<?php

namespace App\Controller\Manage;

use App\Repository\BrandRepository;
use App\Repository\VehicleEngineRepository;
use App\Repository\VehicleModelRepository;
use App\Repository\VehicleVariantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VehicleSearchController extends AbstractController
{
    #[Route('/vehicules/recherche', name: 'manage_vehicle_search', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('manage/vehicle_search/index.html.twig');
    }

    #[Route('/vehicules/recherche/marques', name: 'manage_vehicle_search_brands', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function brands(Request $request, BrandRepository $repository): JsonResponse
    {
        $type = 'moto' === $request->query->get('type') ? 'moto' : 'auto';
        $brands = $repository->findByType($type);

        return new JsonResponse(['options' => array_map(fn ($b) => ['id' => $b->getId(), 'name' => $b->getName()], $brands)]);
    }

    #[Route('/vehicules/recherche/modeles', name: 'manage_vehicle_search_models', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function models(
        Request $request,
        VehicleModelRepository $modelRepository,
        VehicleVariantRepository $variantRepository,
        VehicleEngineRepository $engineRepository
    ): JsonResponse {
        $brandId = (int) $request->query->get('brand');
        $type = 'moto' === $request->query->get('type') ? 'moto' : 'auto';
        $isMoto = 'moto' === $type;

        $models = $modelRepository->findByBrandAndType($brandId, $isMoto);

        $rows = array_map(function (\App\Entity\VehicleModel $model) use ($isMoto, $variantRepository, $engineRepository) {
            if ($isMoto) {
                $primaryCount = $engineRepository->countByModel($model->getId());
                $engineCount = null;
            } else {
                $primaryCount = $variantRepository->countByModel($model->getId());
                $engineCount = $engineRepository->countByModelViaVariants($model->getId());
            }

            return [
                'id' => $model->getId(),
                'name' => $model->getName(),
                'name2' => $model->getName2(),
                'logo' => $model->getBrand()->getLogoName() ? $this->generateUrl('manage_vehicle_models_show', ['id' => $model->getId()]) : null,
                'brandLogoUrl' => $model->getBrand()->getLogoName() ? '/media/logos_brands/' . $model->getBrand()->getLogoName() : null,
                'primaryCount' => $primaryCount,
                'engineCount' => $engineCount,
                'showUrl' => $this->generateUrl('manage_vehicle_models_show', ['id' => $model->getId()]),
                'editUrl' => $this->generateUrl('manage_vehicle_models_edit', ['id' => $model->getId()]),
                'deleteUrl' => $this->generateUrl('manage_vehicle_models_delete', ['id' => $model->getId()]),
            ];
        }, $models);

        $sortField = $request->query->get('sort');
        $sortDir = $request->query->get('dir', 'ASC');
        if ($sortField) {
            $rows = $this->sortRows($rows, $sortField, $sortDir);
        }

        return new JsonResponse([
            'options' => array_map(fn ($m) => ['id' => $m->getId(), 'name' => $m->getName()], $models),
            'rows' => $rows,
        ]);
    }

    #[Route('/vehicules/recherche/variantes', name: 'manage_vehicle_search_variants', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function variants(Request $request, VehicleVariantRepository $variantRepository, VehicleEngineRepository $engineRepository): JsonResponse
    {
        $modelId = (int) $request->query->get('model');
        $variants = $variantRepository->findByModel($modelId);

        $rows = array_map(fn (\App\Entity\VehicleVariant $v) => [
            'id' => $v->getId(),
            'name' => $v->getName() ?: '-',
            'period' => $v->getMonthBegin() . '/' . $v->getYearBegin() . ' — ' . ($v->getMonthEnd() ? $v->getMonthEnd() . '/' . $v->getYearEnd() : '...'),
            'engineCount' => $engineRepository->countByVariant($v->getId()),
            'showUrl' => $this->generateUrl('manage_vehicle_variants_show', ['id' => $v->getId()]),
            'editUrl' => $this->generateUrl('manage_vehicle_variants_edit', ['id' => $v->getId()]),
            'deleteUrl' => $this->generateUrl('manage_vehicle_variants_delete', ['id' => $v->getId()]),
        ], $variants);

        $sortField = $request->query->get('sort');
        $sortDir = $request->query->get('dir', 'ASC');
        if ($sortField) {
            $rows = $this->sortRows($rows, $sortField, $sortDir);
        }

        return new JsonResponse([
            'options' => array_map(fn ($v) => ['id' => $v->getId(), 'name' => ($v->getName() ?: '-') . ' (' . $v->getMonthBegin() . '.' . $v->getYearBegin() . ')'], $variants),
            'rows' => $rows,
        ]);
    }

    #[Route('/vehicules/recherche/motorisations', name: 'manage_vehicle_search_engines', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function engines(Request $request, VehicleEngineRepository $engineRepository): JsonResponse
    {
        $modelId = $request->query->get('model') ? (int) $request->query->get('model') : null;
        $variantId = $request->query->get('variant') ? (int) $request->query->get('variant') : null;

        if ($variantId) {
            $engines = $engineRepository->findByVariant($variantId);
        } elseif ($modelId) {
            $engines = $engineRepository->findByModel($modelId);
        } else {
            $engines = [];
        }

        $rows = array_map(fn (\App\Entity\VehicleEngine $e) => [
            'id' => $e->getId(),
            'label' => $e->getLabel(),
            'powerCv' => $e->getPowerCv(),
            'powerKw' => $e->getPowerKw(),
            'fuelType' => $e->getFuelType()?->getName(),
            'period' => $e->getPeriodLabel(),
            'editUrl' => $this->generateUrl('manage_vehicle_engines_edit', ['id' => $e->getId()]),
            'deleteUrl' => $this->generateUrl('manage_vehicle_engines_delete', ['id' => $e->getId()]),
        ], $engines);

        $sortField = $request->query->get('sort');
        $sortDir = $request->query->get('dir', 'ASC');
        if ($sortField) {
            $rows = $this->sortEngineRows($rows, $sortField, $sortDir);
        }

        return new JsonResponse([
            'options' => array_map(function (\App\Entity\VehicleEngine $e) {
                $period = $e->getMonthStart() && $e->getYearStart()
                    ? $e->getMonthStart() . '.' . $e->getYearStart() . ' - ' . ($e->getMonthEnd() && $e->getYearEnd() ? $e->getMonthEnd() . '.' . $e->getYearEnd() : '...')
                    : null;

                $label = $e->getLabel();
                if ($e->getPowerCv()) {
                    $label .= ', ' . $e->getPowerCv() . ' CV';
                }
                if ($period) {
                    $label .= ' (Année de construction ' . $period . ')';
                }

                return [
                    'id' => $e->getId(),
                    'name' => $label,
                    'group' => $e->getFuelType() ? $e->getFuelType()->getName() : 'Énergie non renseignée',
                ];
            }, $engines),
            'rows' => $rows,
        ]);
    }

    private function sortRows(array $rows, string $field, string $dir): array
    {
        $dirMultiplier = strtoupper($dir) === 'DESC' ? -1 : 1;
        usort($rows, function ($a, $b) use ($field, $dirMultiplier) {
            $valA = $a[$field] ?? '';
            $valB = $b[$field] ?? '';
            return $dirMultiplier * ($valA <=> $valB);
        });
        return $rows;
    }

    private function sortEngineRows(array $rows, string $field, string $dir): array
    {
        return $this->sortRows($rows, $field, $dir);
    }
}