<?php

namespace App\Controller\Manage;

use App\Entity\LicenseType;
use App\Entity\MotorcycleType;
use App\Entity\RentalPeriod;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SimpleReferenceManagementController extends AbstractController
{
    private const TYPES = [
        'permis' => ['class' => LicenseType::class, 'label' => 'Permis requis'],
        'types-moto' => ['class' => MotorcycleType::class, 'label' => 'Types de moto'],
        'periodes-location' => ['class' => RentalPeriod::class, 'label' => 'Périodes de location'],
    ];

    #[Route('/referentiel/{type}', name: 'manage_reference_index', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['type' => 'permis|types-moto|periodes-location'])]
    public function index(string $type, EntityManagerInterface $em): Response
    {
        $config = self::TYPES[$type];
        $items = $em->getRepository($config['class'])->findBy([], ['position' => 'ASC']);

        return $this->render('manage/simple_reference/index.html.twig', [
            'type' => $type,
            'label' => $config['label'],
            'items' => $items,
        ]);
    }

    #[Route('/referentiel/{type}/nouveau', name: 'manage_reference_new', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['type' => 'permis|types-moto|periodes-location'])]
    public function new(string $type): Response
    {
        return $this->render('manage/simple_reference/form.html.twig', [
            'type' => $type,
            'label' => self::TYPES[$type]['label'],
            'item' => null,
        ]);
    }

    #[Route('/referentiel/{type}/nouveau', name: 'manage_reference_create', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['type' => 'permis|types-moto|periodes-location'])]
    public function create(string $type, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $class = self::TYPES[$type]['class'];
        $item = new $class();
        $item->setName((string) $request->request->get('name'));
        $item->setPosition((int) $request->request->get('position', 0));

        $em->persist($item);
        $em->flush();

        $this->addFlash('success', 'Élément créé.');
        return $this->redirectToRoute('manage_reference_index', ['type' => $type]);
    }

    #[Route('/referentiel/{type}/{id}/modifier', name: 'manage_reference_edit', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['type' => 'permis|types-moto|periodes-location'])]
    public function edit(string $type, int $id, EntityManagerInterface $em): Response
    {
        $item = $em->getRepository(self::TYPES[$type]['class'])->find($id) ?? throw $this->createNotFoundException();

        return $this->render('manage/simple_reference/form.html.twig', [
            'type' => $type,
            'label' => self::TYPES[$type]['label'],
            'item' => $item,
        ]);
    }

    #[Route('/referentiel/{type}/{id}/modifier', name: 'manage_reference_update', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['type' => 'permis|types-moto|periodes-location'])]
    public function update(string $type, int $id, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $item = $em->getRepository(self::TYPES[$type]['class'])->find($id) ?? throw $this->createNotFoundException();
        $item->setName((string) $request->request->get('name'));
        $item->setPosition((int) $request->request->get('position', 0));

        $em->flush();

        $this->addFlash('success', 'Élément mis à jour.');
        return $this->redirectToRoute('manage_reference_index', ['type' => $type]);
    }

    #[Route('/referentiel/{type}/{id}/supprimer', name: 'manage_reference_delete', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['type' => 'permis|types-moto|periodes-location'])]
    public function delete(string $type, int $id, EntityManagerInterface $em): RedirectResponse
    {
        $item = $em->getRepository(self::TYPES[$type]['class'])->find($id) ?? throw $this->createNotFoundException();
        $em->remove($item);
        $em->flush();

        $this->addFlash('success', 'Élément supprimé.');
        return $this->redirectToRoute('manage_reference_index', ['type' => $type]);
    }
}