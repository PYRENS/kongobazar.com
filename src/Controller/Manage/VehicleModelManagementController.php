<?php

namespace App\Controller\Manage;

use App\Entity\VehicleModel;
use App\Repository\BrandRepository;
use App\Repository\VehicleModelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VehicleModelManagementController extends AbstractController
{
    #[Route('/vehicules/modeles', name: 'manage_vehicle_models_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(
        Request $request,
        VehicleModelRepository $repository,
        BrandRepository $brandRepository,
        \App\Repository\VehicleVariantRepository $variantRepository,
        \App\Repository\VehicleEngineRepository $engineRepository
    ): Response {
        $brandId = $request->query->get('brand') ? (int) $request->query->get('brand') : null;
        $filterAuto = (bool) $request->query->get('auto');
        $filterMoto = (bool) $request->query->get('moto');
        $searchTerm = $request->query->get('q');

        $models = $repository->findFiltered($brandId, $filterAuto, $filterMoto, $searchTerm);

        $rows = array_map(function (VehicleModel $model) use ($variantRepository, $engineRepository) {
            if ($model->isMoto()) {
                $primaryCount = $engineRepository->countByModel($model->getId());
                $engineCount = null;
            } else {
                $primaryCount = $variantRepository->countByModel($model->getId());
                $engineCount = $engineRepository->countByModelViaVariants($model->getId());
            }

            return ['model' => $model, 'primaryCount' => $primaryCount, 'engineCount' => $engineCount];
        }, $models);

        $sortField = $request->query->get('sort', 'name');
        $sortDir = $request->query->get('dir', 'ASC');
        $rows = $this->sortRows($rows, $sortField, $sortDir);

        return $this->render('manage/vehicle_models/index.html.twig', [
            'rows' => $rows,
            'brands' => $brandRepository->findVehicleBrands(),
            'selectedBrandId' => $brandId,
            'filterAuto' => $filterAuto,
            'filterMoto' => $filterMoto,
            'searchTerm' => $searchTerm,
            'currentSort' => $sortField,
            'currentDir' => $sortDir,
        ]);
    }

    #[Route('/vehicules/modeles/{id}', name: 'manage_vehicle_models_show', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        VehicleModel $model,
        \App\Repository\VehicleVariantRepository $variantRepository,
        \App\Repository\VehicleEngineRepository $engineRepository
    ): Response {
        return $this->render('manage/vehicle_models/show.html.twig', [
            'model' => $model,
            'variants' => $model->isMoto() ? [] : $variantRepository->findByModel($model->getId()),
            'engines' => $model->isMoto() ? $engineRepository->findByModel($model->getId()) : [],
        ]);
    }

    private function sortRows(array $rows, string $field, string $dir): array
    {
        $allowed = ['brand', 'name', 'name2', 'type', 'primaryCount', 'engineCount'];
        if (!in_array($field, $allowed, true)) {
            $field = 'name';
        }
        $dirMultiplier = strtoupper($dir) === 'DESC' ? -1 : 1;

        usort($rows, function ($a, $b) use ($field, $dirMultiplier) {
            $valA = match ($field) {
                'brand' => $a['model']->getBrand()->getName(),
                'name2' => $a['model']->getName2() ?? '',
                'type' => $a['model']->isMoto() ? 'moto' : 'auto',
                'primaryCount' => $a['primaryCount'],
                'engineCount' => $a['engineCount'] ?? -1,
                default => $a['model']->getName(),
            };
            $valB = match ($field) {
                'brand' => $b['model']->getBrand()->getName(),
                'name2' => $b['model']->getName2() ?? '',
                'type' => $b['model']->isMoto() ? 'moto' : 'auto',
                'primaryCount' => $b['primaryCount'],
                'engineCount' => $b['engineCount'] ?? -1,
                default => $b['model']->getName(),
            };
            return $dirMultiplier * ($valA <=> $valB);
        });

        return $rows;
    }

    #[Route('/vehicules/modeles/nouveau', name: 'manage_vehicle_models_new', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function new(BrandRepository $brandRepository): Response
    {
        return $this->render('manage/vehicle_models/form.html.twig', [
            'model' => null,
            'brands' => $brandRepository->findVehicleBrands(),
        ]);
    }

    #[Route('/vehicules/modeles/nouveau', name: 'manage_vehicle_models_create', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $model = new VehicleModel();
        $this->hydrate($model, $request, $em);
        $em->persist($model);
        $em->flush();

        $this->addFlash('success', $model->getName() . ' créé.');
        return $this->redirectToRoute('manage_vehicle_models_index');
    }

    #[Route('/vehicules/modeles/{id}/modifier', name: 'manage_vehicle_models_edit', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function edit(VehicleModel $model, BrandRepository $brandRepository): Response
    {
        return $this->render('manage/vehicle_models/form.html.twig', [
            'model' => $model,
            'brands' => $brandRepository->findVehicleBrands(),
        ]);
    }

    #[Route('/vehicules/modeles/{id}/modifier', name: 'manage_vehicle_models_update', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function update(VehicleModel $model, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $this->hydrate($model, $request, $em);
        $em->flush();

        $this->addFlash('success', $model->getName() . ' mis à jour.');
        return $this->redirectToRoute('manage_vehicle_models_index');
    }

    #[Route('/vehicules/modeles/{id}/supprimer', name: 'manage_vehicle_models_delete', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function delete(VehicleModel $model, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($model);
        $em->flush();

        $this->addFlash('success', 'Modèle supprimé.');
        return $this->redirectToRoute('manage_vehicle_models_index');
    }

    private function hydrate(VehicleModel $model, Request $request, EntityManagerInterface $em): void
    {
        $model->setName((string) $request->request->get('name'));
        $model->setName2($request->request->get('name2') ?: null);

        $brandId = (int) $request->request->get('brand_id');
        $brand = $em->getRepository(\App\Entity\Brand::class)->find($brandId);
        $model->setBrand($brand);

        $model->setType($request->request->get('is_moto') ? 'moto' : null);
    }
}