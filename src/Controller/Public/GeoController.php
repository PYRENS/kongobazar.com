<?php

namespace App\Controller\Public;

use App\Repository\AdministrativeUnitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class GeoController extends AbstractController
{
    #[Route('/geo/children/{parentId}', name: 'geo_children', host: 'kongobazar.com')]
    public function children(int $parentId, AdministrativeUnitRepository $repository): JsonResponse
    {
        $children = $repository->findActiveChildren($parentId);

        $results = array_map(fn ($unit) => [
            'id' => $unit->getId(),
            'name' => $unit->getName(),
            'typeLabel' => $unit->getTypeLabel(),
        ], $children);

        return new JsonResponse(['results' => $results]);
    }
}