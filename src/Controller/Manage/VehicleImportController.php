<?php

namespace App\Controller\Manage;

use App\Entity\Brand;
use App\Entity\FuelType;
use App\Entity\VehicleEngine;
use App\Entity\VehicleModel;
use App\Entity\VehicleVariant;
use App\Service\VehicleImportParser;
use App\Service\VehicleImportReconciler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

class VehicleImportController extends AbstractController
{
    #[Route('/vehicules/import', name: 'manage_vehicle_import', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function form(): Response
    {
        return $this->render('manage/vehicle_import/form.html.twig');
    }

    #[Route('/vehicules/import/analyser', name: 'manage_vehicle_import_analyze', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function analyze(Request $request, VehicleImportParser $parser, VehicleImportReconciler $reconciler): JsonResponse
    {
        $text = (string) $request->request->get('text', '');
        $type = 'moto' === $request->request->get('type') ? 'moto' : 'auto';

        $parsed = $parser->parse($text, $type);
        $result = $reconciler->reconcile($parsed, $type);
        $result['type'] = $type;

        return new JsonResponse($result);
    }

    #[Route('/vehicules/import/creer-marque', name: 'manage_vehicle_import_create_brand', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function createBrand(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $name = trim((string) $request->request->get('name'));
        $type = 'moto' === $request->request->get('type') ? 'moto' : 'auto';

        if ('' === $name) {
            return new JsonResponse(['error' => 'Nom requis.'], 400);
        }

        $existing = $em->getRepository(Brand::class)->findOneBy(['name' => $name]);
        if ($existing) {
            return new JsonResponse(['id' => $existing->getId()]);
        }

        $brand = new Brand();
        $brand->setName($name);
        $brand->setType([$type]);
        $brand->setVerified(false);
        $slugger = new AsciiSlugger();
        $brand->setSlug(strtolower((string) $slugger->slug($name)) . '-' . uniqid());

        $em->persist($brand);
        $em->flush();

        return new JsonResponse(['id' => $brand->getId()]);
    }

    #[Route('/vehicules/import/creer-modele', name: 'manage_vehicle_import_create_model', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function createModel(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $brandId = (int) $request->request->get('brand_id');
        $name = trim((string) $request->request->get('name'));
        $type = 'moto' === $request->request->get('type') ? 'moto' : null;

        $brand = $em->getRepository(Brand::class)->find($brandId);
        if (!$brand || '' === $name) {
            return new JsonResponse(['error' => 'Marque ou nom invalide.'], 400);
        }

        $model = new VehicleModel();
        $model->setBrand($brand);
        $model->setName($name);
        $model->setType($type);

        $em->persist($model);
        $em->flush();

        return new JsonResponse(['id' => $model->getId()]);
    }

    #[Route('/vehicules/import/creer-variante', name: 'manage_vehicle_import_create_variant', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function createVariant(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $modelId = (int) $request->request->get('model_id');
        $name = $request->request->get('name') ?: null;
        $monthBegin = (string) $request->request->get('month_begin');
        $yearBegin = (int) $request->request->get('year_begin');
        $monthEnd = $request->request->get('month_end') ?: null;
        $yearEnd = $request->request->get('year_end') ? (int) $request->request->get('year_end') : null;

        $model = $em->getRepository(VehicleModel::class)->find($modelId);
        if (!$model || '' === $monthBegin || !$yearBegin) {
            return new JsonResponse(['error' => 'Données de variante invalides.'], 400);
        }

        $variant = new VehicleVariant();
        $variant->setModel($model);
        $variant->setName($name);
        $variant->setMonthBegin($monthBegin);
        $variant->setYearBegin($yearBegin);
        $variant->setMonthEnd($monthEnd);
        $variant->setYearEnd($yearEnd);

        $em->persist($variant);
        $em->flush();

        return new JsonResponse(['id' => $variant->getId()]);
    }

    #[Route('/vehicules/import/creer-energie', name: 'manage_vehicle_import_create_fuel', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function createFuel(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $name = trim((string) $request->request->get('name'));
        if ('' === $name) {
            return new JsonResponse(['error' => 'Nom requis.'], 400);
        }

        $existing = $em->getRepository(FuelType::class)->findOneBy(['name' => $name]);
        if ($existing) {
            return new JsonResponse(['id' => $existing->getId()]);
        }

        $fuel = new FuelType();
        $fuel->setName($name);
        $fuel->setActive(true);

        $em->persist($fuel);
        $em->flush();

        return new JsonResponse(['id' => $fuel->getId()]);
    }

    #[Route('/vehicules/import/enregistrer', name: 'manage_vehicle_import_save', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function save(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'Payload invalide.'], 400);
        }

        $type = 'moto' === ($payload['type'] ?? null) ? 'moto' : 'auto';
        $brand = $em->getRepository(Brand::class)->find((int) ($payload['brandId'] ?? 0));
        $model = $em->getRepository(VehicleModel::class)->find((int) ($payload['modelId'] ?? 0));
        $variant = !empty($payload['variantId']) ? $em->getRepository(VehicleVariant::class)->find((int) $payload['variantId']) : null;

        if (!$brand || !$model || ('auto' === $type && !$variant)) {
            return new JsonResponse(['error' => 'Marque, modèle ou variante manquant(e).'], 400);
        }

        $created = 0;
        foreach ($payload['rows'] as $row) {
            if (!empty($row['engineExists'])) {
                continue;
            }

            $engine = new VehicleEngine();
            if ('auto' === $type) {
                $engine->setVariant($variant);
            } else {
                $engine->setModel($model);
            }

            $engine->setLabel((string) $row['label']);
            $engine->setPowerCv(!empty($row['powerCv']) ? (int) $row['powerCv'] : null);
            $engine->setPowerKw(!empty($row['powerKw']) ? (int) $row['powerKw'] : null);
            $engine->setDisplacementCc(!empty($row['displacementCc']) ? (int) $row['displacementCc'] : null);

            if (!empty($row['fuelId'])) {
                $engine->setFuelType($em->getRepository(FuelType::class)->find((int) $row['fuelId']));
            }

            $begin = $row['periodBegin'] ?? null;
            $end = $row['periodEnd'] ?? null;
            $engine->setMonthStart($begin['month'] ?? null);
            $engine->setYearStart($begin['year'] ?? null);
            $engine->setMonthEnd($end['month'] ?? null);
            $engine->setYearEnd($end['year'] ?? null);

            $engine->setBrandNameCache($brand->getName());
            $engine->setModelNameCache($model->getName());
            $engine->setVariantNameCache($variant?->getName());

            $periodStart = $begin ? $begin['month'] . '.' . $begin['year'] : null;
            $periodEnd = $end ? $end['month'] . '.' . $end['year'] : '...';
            $engine->setPeriodLabel($periodStart ? $periodStart . ' - ' . $periodEnd : null);

            $em->persist($engine);
            $created++;
        }

        $em->flush();

        return new JsonResponse(['created' => $created]);
    }
}