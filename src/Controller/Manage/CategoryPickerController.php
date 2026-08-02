<?php

namespace App\Controller\Manage;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class CategoryPickerController extends AbstractController
{
    #[Route('/categories/enfants/{parentId}', name: 'manage_categories_children', host: 'manage.kongobazar.com')]
    public function children(int $parentId, Request $request, CategoryRepository $repository): JsonResponse
    {
        $excludeId = $request->query->get('exclude') ? (int) $request->query->get('exclude') : null;
        $children = $repository->findChildrenOf($parentId, $excludeId);

        return new JsonResponse(['results' => array_map(fn ($c) => [
            'id' => $c->getId(),
            'name' => $c->getName(),
        ], $children)]);
    }
}