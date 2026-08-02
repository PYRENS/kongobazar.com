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
        $brands = $repository->findBy([], ['name' => 'ASC']);

        $rows = array_map(function (\App\Entity\Brand $brand) use ($vehicleModelRepository, $productRepository) {
            $isVehicle = $brand->hasType('auto') || $brand->hasType('moto');

            return [
                'brand' => $brand,
                'count' => $isVehicle
                    ? $vehicleModelRepository->countByBrand($brand->getId())
                    : $productRepository->countByBrand($brand->getId()),
                'countLabel' => $isVehicle ? 'modèle(s)' : 'produit(s)',
            ];
        }, $brands);

        $sortField = $request->query->get('sort', 'name');
        $sortDir = $request->query->get('dir', 'ASC');
        $rows = $this->sortRows($rows, $sortField, $sortDir);

        return $this->render('manage/brands/index.html.twig', [
            'rows' => $rows,
            'currentSort' => $sortField,
            'currentDir' => $sortDir,
        ]);
    }

    #[Route('/marques/{id}', name: 'manage_brands_show', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        Brand $brand,
        \App\Repository\VehicleModelRepository $vehicleModelRepository,
        \App\Repository\ProductRepository $productRepository
    ): Response {
        $isVehicle = $brand->hasType('auto') || $brand->hasType('moto');

        return $this->render('manage/brands/show.html.twig', [
            'brand' => $brand,
            'isVehicle' => $isVehicle,
            'vehicleModels' => $isVehicle ? $vehicleModelRepository->findByBrand($brand->getId()) : [],
            'products' => $isVehicle ? [] : $productRepository->findByBrand($brand->getId()),
        ]);
    }

    #[Route('/marques/{id}/basculer', name: 'manage_brands_toggle', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(Brand $brand, EntityManagerInterface $em): RedirectResponse
    {
        $brand->setActive(!$brand->isActive());
        $em->flush();

        $this->addFlash('success', $brand->getName() . ($brand->isActive() ? ' activée.' : ' désactivée.'));
        return $this->redirectToRoute('manage_brands_index');
    }

    private function sortRows(array $rows, string $field, string $dir): array
    {
        $allowed = ['name', 'pays', 'type', 'verified', 'count'];
        if (!in_array($field, $allowed, true)) {
            $field = 'name';
        }
        $dirMultiplier = strtoupper($dir) === 'DESC' ? -1 : 1;

        usort($rows, function ($a, $b) use ($field, $dirMultiplier) {
            $valA = match ($field) {
                'pays' => $a['brand']->getPays()?->getName() ?? '',
                'type' => $a['brand']->getType() ? implode(',', $a['brand']->getType()) : '',
                'verified' => (int) $a['brand']->isVerified(),
                'count' => $a['count'],
                default => $a['brand']->getName(),
            };
            $valB = match ($field) {
                'pays' => $b['brand']->getPays()?->getName() ?? '',
                'type' => $b['brand']->getType() ? implode(',', $b['brand']->getType()) : '',
                'verified' => (int) $b['brand']->isVerified(),
                'count' => $b['count'],
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

        $logoFile = $request->files->get('logo');
        if ($logoFile) {
            $brand->setLogoFile($logoFile);
        }

        $types = $request->request->all('type');
        $brand->setType(empty($types) ? null : array_values($types));

        $paysId = $request->request->get('pays_id') ? (int) $request->request->get('pays_id') : null;
        $brand->setPays($paysId ? $em->getRepository(\App\Entity\Pays::class)->find($paysId) : null);

        $brand->setActive((bool) $request->request->get('active'));

        if (null === $brand->getSlug()) {
            $slugger = new AsciiSlugger();
            $brand->setSlug(strtolower($slugger->slug($name)) . '-' . uniqid());
        }
    }
}