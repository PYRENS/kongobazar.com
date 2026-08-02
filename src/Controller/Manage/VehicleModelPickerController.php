<?php

namespace App\Controller\Manage;

use App\Repository\VehicleModelRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class VehicleModelPickerController extends AbstractController
{
    #[Route('/vehicules/marques/{brandId}/modeles', name: 'manage_vehicle_brand_models', host: 'manage.kongobazar.com')]
    public function children(int $brandId, VehicleModelRepository $repository): JsonResponse
    {
        $models = $repository->findByBrand($brandId);

        return new JsonResponse(['results' => array_map(fn ($m) => [
            'id' => $m->getId(),
            'name' => $m->getName(),
        ], $models)]);
    }

    #[Route('/vehicules/modeles/{modelId}/variantes', name: 'manage_vehicle_model_variants', host: 'manage.kongobazar.com')]
    public function variants(int $modelId, \App\Repository\VehicleVariantRepository $variantRepository): JsonResponse
    {
        $variants = $variantRepository->findByModel($modelId);

        return new JsonResponse(['results' => array_map(fn ($v) => [
            'id' => $v->getId(),
            'name' => ($v->getName() ?: '-') . ' (' . $v->getMonthBegin() . '.' . $v->getYearBegin() . ' - ' . ($v->getMonthEnd() ? $v->getMonthEnd() . '.' . $v->getYearEnd() : '...') . ')',
        ], $variants)]);
    }
}