<?php

namespace App\Controller\Manage;

use App\Entity\VehicleEngine;
use App\Entity\VehicleModel;
use App\Entity\VehicleVariant;
use App\Repository\BrandRepository;
use App\Service\PartCompatibilityParser;
use App\Service\PartCompatibilityReconciler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class PartCompatibilityImportController extends AbstractController
{
    #[Route('/produits/pieces/analyser-compatibilite', name: 'manage_part_compat_analyze', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function analyze(Request $request, PartCompatibilityParser $parser, PartCompatibilityReconciler $reconciler): JsonResponse
    {
        $text = (string) $request->request->get('text', '');
        $parsed = $parser->parse($text);
        $result = $reconciler->reconcile($parsed);

        return new JsonResponse($result);
    }

    #[Route('/produits/pieces/creer-marque', name: 'manage_part_compat_create_brand', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function createBrand(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $name = trim((string) $request->request->get('name'));
        if ('' === $name) {
            return new JsonResponse(['error' => 'Nom requis.'], 400);
        }

        $existing = $em->getRepository(\App\Entity\Brand::class)->findOneBy(['name' => $name]);
        if ($existing) {
            return new JsonResponse(['id' => $existing->getId()]);
        }

        $brand = new \App\Entity\Brand();
        $brand->setName($name);
        $brand->setType(['auto']);
        $brand->setVerified(false);
        $slugger = new \Symfony\Component\String\Slugger\AsciiSlugger();
        $brand->setSlug(strtolower((string) $slugger->slug($name)) . '-' . uniqid());

        $em->persist($brand);
        $em->flush();

        return new JsonResponse(['id' => $brand->getId()]);
    }

    #[Route('/produits/pieces/creer-modele', name: 'manage_part_compat_create_model', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function createModel(Request $request, EntityManagerInterface $em, BrandRepository $brandRepository): JsonResponse
    {
        $brandId = (int) $request->request->get('brand_id');
        $name = trim((string) $request->request->get('name'));

        $brand = $brandRepository->find($brandId);
        if (!$brand || '' === $name) {
            return new JsonResponse(['error' => 'Marque ou nom invalide.'], 400);
        }

        $model = new VehicleModel();
        $model->setBrand($brand);
        $model->setName($name);

        $em->persist($model);
        $em->flush();

        return new JsonResponse(['id' => $model->getId()]);
    }

    #[Route('/produits/pieces/creer-variante', name: 'manage_part_compat_create_variant', host: 'manage.kongobazar.com', methods: ['POST'])]
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

    #[Route('/produits/pieces/creer-motorisation', name: 'manage_part_compat_create_engine', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function createEngine(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $variantId = (int) $request->request->get('variant_id');
        $variant = $em->getRepository(VehicleVariant::class)->find($variantId);
        if (!$variant) {
            return new JsonResponse(['error' => 'Variante invalide.'], 400);
        }

        $engine = new VehicleEngine();
        $engine->setVariant($variant);
        $engine->setLabel((string) $request->request->get('label'));
        $engine->setPowerCv($request->request->get('power_cv') ? (int) $request->request->get('power_cv') : null);
        $engine->setDisplacementCc($request->request->get('displacement_cc') ? (int) $request->request->get('displacement_cc') : null);

        $begin = json_decode((string) $request->request->get('period_begin'), true);
        $end = json_decode((string) $request->request->get('period_end'), true);
        $engine->setMonthStart($begin['month'] ?? null);
        $engine->setYearStart($begin['year'] ?? null);
        $engine->setMonthEnd($end['month'] ?? null);
        $engine->setYearEnd($end['year'] ?? null);

        $engine->setBrandNameCache($variant->getModel()->getBrand()->getName());
        $engine->setModelNameCache($variant->getModel()->getName());
        $engine->setVariantNameCache($variant->getName());

        $periodStart = $begin ? $begin['month'] . '.' . $begin['year'] : null;
        $periodEnd = $end ? $end['month'] . '.' . $end['year'] : '...';
        $engine->setPeriodLabel($periodStart ? $periodStart . ' - ' . $periodEnd : null);

        $em->persist($engine);
        $em->flush();

        return new JsonResponse(['id' => $engine->getId()]);
    }
}