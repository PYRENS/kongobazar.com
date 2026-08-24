<?php

namespace App\Controller\Manage;

use App\Entity\Brand;
use App\Repository\BrandRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

class BrandManagementController extends AbstractController
{
    #[Route('/marques', name: 'manage_brands_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(
        Request $request,
        BrandRepository $repository,
        \App\Repository\VehicleModelRepository $vehicleModelRepository,
        \App\Repository\ProductRepository $productRepository
    ): Response {
        $allBrands = $repository->findBy([], ['name' => 'ASC']);

        $term = $request->query->get('q') ?: null;
        $brands = $term
            ? array_values(array_filter($allBrands, fn (\App\Entity\Brand $b) => str_contains(mb_strtolower($b->getName()), mb_strtolower($term))))
            : $allBrands;

        $rows = array_map(function (\App\Entity\Brand $brand) use ($vehicleModelRepository, $productRepository) {
            $isVehicle = $brand->hasType('auto') || $brand->hasType('moto');

            return [
                'brand' => $brand,
                'isVehicle' => $isVehicle,
                'productCount' => $productRepository->countByBrand($brand->getId()),
                'modelCount' => $isVehicle ? $vehicleModelRepository->countByBrand($brand->getId()) : null,
            ];
        }, $brands);

        $sortField = $request->query->get('sort', 'name');
        $sortDir = $request->query->get('dir', 'ASC');
        $rows = $this->sortRows($rows, $sortField, $sortDir);

        $total = count($rows);
        $perPage = in_array((int) $request->query->get('perPage', 20), [10, 20, 50, 100], true)
            ? (int) $request->query->get('perPage', 20) : 20;
        $page = max(1, (int) $request->query->get('page', 1));
        $rows = array_slice($rows, ($page - 1) * $perPage, $perPage);

        $stats = [
            'total' => count($allBrands),
            'active' => count(array_filter($allBrands, fn (\App\Entity\Brand $b) => $b->isActive())),
            'verified' => count(array_filter($allBrands, fn (\App\Entity\Brand $b) => $b->isVerified())),
            'vehicle' => count(array_filter($allBrands, fn (\App\Entity\Brand $b) => $b->hasType('auto') || $b->hasType('moto'))),
        ];

        return $this->render('manage/brands/index.html.twig', [
            'rows' => $rows,
            'currentSort' => $sortField,
            'currentDir' => $sortDir,
            'searchTerm' => $term,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'perPage' => $perPage,
            'total' => $total,
            'stats' => $stats,
            'allBrandNames' => array_map(fn (\App\Entity\Brand $b) => $b->getName(), $allBrands),
        ]);
    }

    #[Route('/marques/{id}', name: 'manage_brands_show', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        Brand $brand,
        Request $request,
        \App\Repository\VehicleModelRepository $vehicleModelRepository,
        \App\Repository\VehicleVariantRepository $vehicleVariantRepository,
        \App\Repository\VehicleEngineRepository $vehicleEngineRepository,
        \App\Repository\ProductRepository $productRepository
    ): Response {
        $isAuto = $brand->hasType('auto');
        $isMoto = $brand->hasType('moto');
        $isVehicle = $isAuto || $isMoto;

        $modelRows = [];
        if ($isVehicle) {
            $models = $vehicleModelRepository->findByBrand($brand->getId());
            $modelRows = array_map(function (\App\Entity\VehicleModel $model) use ($vehicleVariantRepository, $vehicleEngineRepository) {
                if ($model->isMoto()) {
                    $primaryCount = $vehicleEngineRepository->countByModel($model->getId());
                    $engineCount = null;
                } else {
                    $primaryCount = $vehicleVariantRepository->countByModel($model->getId());
                    $engineCount = $vehicleEngineRepository->countByModelViaVariants($model->getId());
                }

                return ['model' => $model, 'primaryCount' => $primaryCount, 'engineCount' => $engineCount];
            }, $models);

            $sortField = $request->query->get('sort', 'name');
            $sortDir = $request->query->get('dir', 'ASC');
            $modelRows = $this->sortModelRows($modelRows, $sortField, $sortDir);
        }

        return $this->render('manage/brands/show.html.twig', [
            'brand' => $brand,
            'isVehicle' => $isVehicle,
            'isAuto' => $isAuto,
            'isMoto' => $isMoto,
            'modelRows' => $modelRows,
            'currentSort' => $request->query->get('sort', 'name'),
            'currentDir' => $request->query->get('dir', 'ASC'),
            'autoModelCount' => $isAuto ? $vehicleModelRepository->countByBrandAndType($brand->getId(), false) : 0,
            'autoVariantCount' => $isAuto ? $vehicleVariantRepository->countByBrand($brand->getId()) : 0,
            'autoEngineCount' => $isAuto ? $vehicleEngineRepository->countAutoByBrand($brand->getId()) : 0,
            'motoModelCount' => $isMoto ? $vehicleModelRepository->countByBrandAndType($brand->getId(), true) : 0,
            'motoEngineCount' => $isMoto ? $vehicleEngineRepository->countMotoByBrand($brand->getId()) : 0,
            'productCount' => $isVehicle ? 0 : $productRepository->countByBrand($brand->getId()),
        ]);
    }

    private function sortModelRows(array $rows, string $field, string $dir): array
    {
        $allowed = ['name', 'name2', 'type', 'primaryCount', 'engineCount'];
        if (!in_array($field, $allowed, true)) {
            $field = 'name';
        }
        $dirMultiplier = strtoupper($dir) === 'DESC' ? -1 : 1;

        usort($rows, function ($a, $b) use ($field, $dirMultiplier) {
            $valA = match ($field) {
                'name2' => $a['model']->getName2() ?? '',
                'type' => $a['model']->isMoto() ? 'moto' : 'auto',
                'primaryCount' => $a['primaryCount'],
                'engineCount' => $a['engineCount'] ?? -1,
                default => $a['model']->getName(),
            };
            $valB = match ($field) {
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

    #[Route('/marques/{id}/produits', name: 'manage_brands_products', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function products(Brand $brand, Request $request, \App\Repository\ProductRepository $productRepository): Response
    {
        $status = $request->query->get('status') ?: null;
        $term = $request->query->get('q') ?: null;
        $sort = $request->query->get('sort', 'createdAt');
        $dir = $request->query->get('dir', 'DESC');
        $perPage = in_array((int) $request->query->get('perPage', 20), [10, 20, 50, 100], true)
            ? (int) $request->query->get('perPage', 20) : 20;
        $page = max(1, (int) $request->query->get('page', 1));

        $total = $productRepository->countFilteredByBrand($brand->getId(), $status, $term);
        $allTitles = array_map(
            fn (\App\Entity\Product $p) => $p->getTitle(),
            $productRepository->findFilteredByBrand($brand->getId(), null, null, 1, 1000)
        );

        return $this->render('manage/brands/products.html.twig', [
            'brand' => $brand,
            'products' => $productRepository->findFilteredByBrand($brand->getId(), $status, $term, $page, $perPage, $sort, $dir),
            'currentStatus' => $status,
            'currentTerm' => $term,
            'currentSort' => $sort,
            'currentDir' => $dir,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'perPage' => $perPage,
            'total' => $total,
            'allProductTitles' => $allTitles,
        ]);
    }

    #[Route('/marques/{id}/basculer', name: 'manage_brands_toggle', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(Brand $brand, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $brand->setActive(!$brand->isActive());
        $em->flush();

        $this->addFlash('success', $brand->getName() . ($brand->isActive() ? ' activée.' : ' désactivée.'));

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('manage_brands_index');
    }

    private function sortRows(array $rows, string $field, string $dir): array
    {
        $allowed = ['name', 'sigle', 'pays', 'type', 'verified', 'productCount', 'modelCount', 'active', 'createdAt'];
        if (!in_array($field, $allowed, true)) {
            $field = 'name';
        }
        $dirMultiplier = strtoupper($dir) === 'DESC' ? -1 : 1;

        usort($rows, function ($a, $b) use ($field, $dirMultiplier) {
            $valA = match ($field) {
                'sigle' => $a['brand']->getSigle() ?? '',
                'pays' => $a['brand']->getPays()?->getName() ?? '',
                'type' => $a['brand']->getType() ? implode(',', $a['brand']->getType()) : '',
                'verified' => (int) $a['brand']->isVerified(),
                'productCount' => $a['productCount'],
                'modelCount' => $a['modelCount'] ?? -1,
                'active' => (int) $a['brand']->isActive(),
                'createdAt' => $a['brand']->getCreatedAt()?->getTimestamp() ?? 0,
                default => $a['brand']->getName(),
            };
            $valB = match ($field) {
                'sigle' => $b['brand']->getSigle() ?? '',
                'pays' => $b['brand']->getPays()?->getName() ?? '',
                'type' => $b['brand']->getType() ? implode(',', $b['brand']->getType()) : '',
                'verified' => (int) $b['brand']->isVerified(),
                'productCount' => $b['productCount'],
                'modelCount' => $b['modelCount'] ?? -1,
                'active' => (int) $b['brand']->isActive(),
                'createdAt' => $b['brand']->getCreatedAt()?->getTimestamp() ?? 0,
                default => $b['brand']->getName(),
            };
            return $dirMultiplier * ($valA <=> $valB);
        });

        return $rows;
    }

    #[Route('/marques/nouveau', name: 'manage_brands_new', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function new(\App\Repository\PaysRepository $paysRepository): Response
    {
        return $this->render('manage/brands/form.html.twig', [
            'brand' => null,
            'paysList' => $paysRepository->findBy([], ['nameFr' => 'ASC']),
        ]);
    }

    #[Route('/marques/nouveau', name: 'manage_brands_create', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, BrandRepository $repository): RedirectResponse
    {
        $name = (string) $request->request->get('name');
        if ($repository->findOneBy(['name' => $name])) {
            $this->addFlash('error', 'Une marque nommée "' . $name . '" existe déjà.');
            return $this->redirectToRoute('manage_brands_new');
        }

        $brand = new Brand();
        $this->hydrate($brand, $request, $em);
        $em->persist($brand);

        try {
            $em->flush();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
            $this->addFlash('error', 'Une marque nommée "' . $name . '" existe déjà.');
            return $this->redirectToRoute('manage_brands_new');
        }

        $this->addFlash('success', $brand->getName() . ' créée.');
        return $this->redirectToRoute('manage_brands_index');
    }

    #[Route('/marques/{id}/modifier', name: 'manage_brands_edit', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function edit(Brand $brand, \App\Repository\PaysRepository $paysRepository): Response
    {
        return $this->render('manage/brands/form.html.twig', [
            'brand' => $brand,
            'paysList' => $paysRepository->findBy([], ['nameFr' => 'ASC']),
        ]);
    }

    #[Route('/marques/{id}/modifier', name: 'manage_brands_update', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(Brand $brand, Request $request, EntityManagerInterface $em, BrandRepository $repository): RedirectResponse
    {
        $name = (string) $request->request->get('name');
        $existing = $repository->findOneBy(['name' => $name]);
        if ($existing && $existing->getId() !== $brand->getId()) {
            $this->addFlash('error', 'Une marque nommée "' . $name . '" existe déjà.');
            return $this->redirectToRoute('manage_brands_edit', ['id' => $brand->getId()]);
        }

        $this->hydrate($brand, $request, $em);

        try {
            $em->flush();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
            $this->addFlash('error', 'Une marque nommée "' . $name . '" existe déjà.');
            return $this->redirectToRoute('manage_brands_edit', ['id' => $brand->getId()]);
        }

        $this->addFlash('success', $brand->getName() . ' mise à jour.');
        return $this->redirectToRoute('manage_brands_index');
    }

    #[Route('/marques/{id}/supprimer', name: 'manage_brands_delete', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Brand $brand, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($brand);
        $em->flush();

        $this->addFlash('success', 'Marque supprimée.');
        return $this->redirectToRoute('manage_brands_index');
    }

    private function hydrate(Brand $brand, Request $request, EntityManagerInterface $em): void
    {
        $name = (string) $request->request->get('name');
        $brand->setName($name);
        $brand->setVerified((bool) $request->request->get('verified'));
        $brand->setPremium((bool) $request->request->get('premium'));

        $logoFile = $request->files->get('logo');
        if ($logoFile) {
            $brand->setLogoFile($logoFile);
        }

        $types = $request->request->all('type');
        $brand->setType(empty($types) ? null : array_values($types));

        $paysId = $request->request->get('pays_id') ? (int) $request->request->get('pays_id') : null;
        $brand->setPays($paysId ? $em->getRepository(\App\Entity\Pays::class)->find($paysId) : null);

        $brand->setActive((bool) $request->request->get('active'));
        $brand->setSigle($request->request->get('sigle') ?: null);

        if (null === $brand->getSlug()) {
            $slugger = new AsciiSlugger();
            $brand->setSlug(strtolower($slugger->slug($name)) . '-' . uniqid());
        }
    }
}