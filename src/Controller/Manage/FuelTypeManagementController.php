<?php

namespace App\Controller\Manage;

use App\Entity\FuelType;
use App\Repository\FuelTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FuelTypeManagementController extends AbstractController
{
    #[Route('/energies', name: 'manage_fuel_types_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(Request $request, FuelTypeRepository $repository): Response
    {
        $searchTerm = $request->query->get('q');
        $sortField = $request->query->get('sort', 'name');
        $sortDir = $request->query->get('dir', 'ASC');

        return $this->render('manage/fuel_types/index.html.twig', [
            'fuelTypes' => $repository->findFiltered($searchTerm, $sortField, $sortDir),
            'searchTerm' => $searchTerm,
            'currentSort' => $sortField,
            'currentDir' => $sortDir,
        ]);
    }

    #[Route('/energies/nouveau', name: 'manage_fuel_types_new', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('manage/fuel_types/form.html.twig', ['fuelType' => null]);
    }

    #[Route('/energies/nouveau', name: 'manage_fuel_types_create', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $fuelType = new FuelType();
        $this->hydrate($fuelType, $request);
        $em->persist($fuelType);
        $em->flush();

        $this->addFlash('success', $fuelType->getName() . ' créée.');
        return $this->redirectToRoute('manage_fuel_types_index');
    }

    #[Route('/energies/{id}/modifier', name: 'manage_fuel_types_edit', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function edit(FuelType $fuelType): Response
    {
        return $this->render('manage/fuel_types/form.html.twig', ['fuelType' => $fuelType]);
    }

    #[Route('/energies/{id}/modifier', name: 'manage_fuel_types_update', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function update(FuelType $fuelType, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $this->hydrate($fuelType, $request);
        $em->flush();

        $this->addFlash('success', $fuelType->getName() . ' mise à jour.');
        return $this->redirectToRoute('manage_fuel_types_index');
    }

    #[Route('/energies/{id}/supprimer', name: 'manage_fuel_types_delete', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function delete(FuelType $fuelType, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($fuelType);
        $em->flush();

        $this->addFlash('success', 'Énergie supprimée.');
        return $this->redirectToRoute('manage_fuel_types_index');
    }

    private function hydrate(FuelType $fuelType, Request $request): void
    {
        $fuelType->setName((string) $request->request->get('name'));
        $fuelType->setActive((bool) $request->request->get('active'));
        $fuelType->setDescription($request->request->get('description') ?: null);
    }
}