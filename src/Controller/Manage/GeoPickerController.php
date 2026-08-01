<?php

namespace App\Controller\Manage;

use App\Repository\AdministrativeUnitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class GeoPickerController extends AbstractController
{
    #[Route('/geo/enfants/{parentId}', name: 'manage_geo_children', host: 'manage.kongobazar.com')]
    public function children(int $parentId, AdministrativeUnitRepository $repository): JsonResponse
    {
        $children = $repository->findChildrenOf($parentId);

        return new JsonResponse(['results' => array_map(fn ($u) => [
            'id' => $u->getId(),
            'name' => $u->getName(),
            'typeLabel' => $u->getTypeLabel(),
        ], $children)]);
    }
}