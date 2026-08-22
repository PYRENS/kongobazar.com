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

    private const ICONS = [
        'permis' => 'bi-card-checklist',
        'types-moto' => 'bi-scooter',
        'periodes-location' => 'bi-calendar-range',
    ];

    #[Route('/referentiel/{type}', name: 'manage_reference_index', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['type' => 'permis|types-moto|periodes-location'])]
    public function index(string $type, Request $request, EntityManagerInterface $em): Response
    {
        $config = self::TYPES[$type];
        $searchTerm = $request->query->get('q');
        $sortField = $request->query->get('sort', 'position');
        $sortDir = $request->query->get('dir', 'ASC');

        $sortField = in_array($sortField, ['name', 'position', 'usageCount'], true) ? $sortField : 'position';
        $repo = $em->getRepository($config['class']);

        $allItems = $repo->findBy([], ['position' => 'ASC']);
        $allNames = array_map(fn ($i) => $i->getName(), $allItems);

        $qb = $repo->createQueryBuilder('i');
        if ($sortField !== 'usageCount') {
            $qb->orderBy('i.' . $sortField, strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC');
        }
        if ($searchTerm) {
            $qb->andWhere('i.name LIKE :term')->setParameter('term', '%' . $searchTerm . '%');
        }
        $items = $qb->getQuery()->getResult();

        $rows = array_map(fn ($item) => [
            'item' => $item,
            'usageCount' => $this->countUsage($type, $item->getId(), $em),
        ], $items);

        if ($sortField === 'usageCount') {
            $dirMultiplier = strtoupper($sortDir) === 'DESC' ? -1 : 1;
            usort($rows, fn ($a, $b) => $dirMultiplier * ($a['usageCount'] <=> $b['usageCount']));
        }

        $total = count($rows);
        $perPage = in_array((int) $request->query->get('perPage', 20), [10, 20, 50, 100], true)
            ? (int) $request->query->get('perPage', 20) : 20;
        $page = max(1, (int) $request->query->get('page', 1));
        $rows = array_slice($rows, ($page - 1) * $perPage, $perPage);

        return $this->render('manage/simple_reference/index.html.twig', [
            'type' => $type,
            'label' => $config['label'],
            'icon' => self::ICONS[$type],
            'usageLabel' => self::USAGE_LABELS[$type],
            'rows' => $rows,
            'searchTerm' => $searchTerm,
            'currentSort' => $sortField,
            'currentDir' => $sortDir,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'perPage' => $perPage,
            'total' => $total,
            'allNames' => $allNames,
            'stats' => [
                'total' => count($allItems),
                'used' => count(array_filter($rows, fn ($r) => $r['usageCount'] > 0)),
                'filtered' => $total,
            ],
        ]);
    }

    private const USAGE_LABELS = [
        'permis' => 'véhicules',
        'types-moto' => 'motos',
        'periodes-location' => 'annonces',
    ];

    private function countUsage(string $type, int $itemId, EntityManagerInterface $em): int
    {
        return match ($type) {
            'permis' => (int) $em->getRepository(\App\Entity\VehicleListingDetails::class)
                ->createQueryBuilder('v')->select('COUNT(v.id)')
                ->andWhere('v.licenseType = :id')->setParameter('id', $itemId)
                ->getQuery()->getSingleScalarResult(),
            'types-moto' => (int) $em->getRepository(\App\Entity\VehicleListingDetails::class)
                ->createQueryBuilder('v')->select('COUNT(v.id)')
                ->andWhere('v.motorcycleType = :id')->setParameter('id', $itemId)
                ->getQuery()->getSingleScalarResult(),
            'periodes-location' => (int) $em->getRepository(\App\Entity\PropertyListingDetails::class)
                ->createQueryBuilder('p')->select('COUNT(p.id)')
                ->andWhere('p.rentalPeriod = :id')->setParameter('id', $itemId)
                ->getQuery()->getSingleScalarResult(),
            default => 0,
        };
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