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
    public function index(Request $request, VehicleVariantRepository $repository, VehicleModelRepository $modelRepository): Response
    {
        $modelId = $request->query->get('model') ? (int) $request->query->get('model') : null;

        return $this->render('manage/vehicle_variants/index.html.twig', [
            'variants' => $modelId ? $repository->findByModel($modelId) : $repository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/vehicules/variantes/nouveau', name: 'manage_vehicle_variants_new', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function new(BrandRepository $brandRepository): Response
    {
        return $this->render('manage/vehicle_variants/form.html.twig', [
            'variant' => null,
            'brands' => $brandRepository->findBy([], ['name' => 'ASC']),
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