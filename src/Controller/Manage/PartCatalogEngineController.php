<?php

namespace App\Controller\Manage;

use App\Entity\PartCatalogEngineCompatibility;
use App\Entity\PartCatalogEntry;
use App\Entity\VehicleEngine;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class PartCatalogEngineController extends AbstractController
{
    #[Route('/pieces-catalogue/{id}/motorisations/attacher', name: 'manage_part_catalog_attach_engine', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function attach(PartCatalogEntry $entry, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $engineId = (int) $request->request->get('engine_id');
        $engine = $em->getRepository(VehicleEngine::class)->find($engineId);
        if (!$engine) {
            return new JsonResponse(['error' => 'Motorisation invalide.'], 400);
        }

        foreach ($entry->getEngineCompatibilities() as $existing) {
            if ($existing->getVehicleEngine()->getId() === $engine->getId()) {
                return new JsonResponse(['id' => $existing->getId()]);
            }
        }

        $compat = new PartCatalogEngineCompatibility();
        $compat->setVehicleEngine($engine);
        $entry->addEngineCompatibility($compat);
        $entry->setUpdatedAt(new \DateTimeImmutable());
        $em->persist($compat);
        $em->flush();

        return new JsonResponse(['id' => $compat->getId()]);
    }

    #[Route('/pieces-catalogue/{id}/motorisations/{compatId}/detacher', name: 'manage_part_catalog_detach_engine', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'compatId' => '\d+'])]
    public function detach(PartCatalogEntry $entry, int $compatId, EntityManagerInterface $em): JsonResponse
    {
        foreach ($entry->getEngineCompatibilities() as $compat) {
            if ($compat->getId() === $compatId) {
                $entry->removeEngineCompatibility($compat);
                $em->remove($compat);
                $entry->setUpdatedAt(new \DateTimeImmutable());
                $em->flush();
                break;
            }
        }

        return new JsonResponse(['ok' => true]);
    }
}