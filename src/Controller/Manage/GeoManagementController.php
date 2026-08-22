<?php

namespace App\Controller\Manage;

use App\Entity\AdministrativeUnit;
use App\Repository\AdministrativeUnitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

class GeoManagementController extends AbstractController
{
    #[Route('/territoires', name: 'manage_geo_index', host: 'manage.kongobazar.com')]
    public function index(Request $request, AdministrativeUnitRepository $repository, \App\Repository\UserRepository $userRepository): Response
    {
        $searchTerm = $request->query->get('q');
        $sortField = $request->query->get('sort', 'name');
        $sortDir = $request->query->get('dir', 'ASC');

        if ($searchTerm) {
            $units = $repository->searchByName($searchTerm);
            $rows = $this->buildRows($units, $repository, $userRepository);
            $rows = $this->sortRows($rows, $sortField, $sortDir);

            return $this->render('manage/geo/index.html.twig', [
                'rows' => $rows,
                'parent' => null,
                'breadcrumb' => [],
                'searchTerm' => $searchTerm,
                'isSearchMode' => true,
                'currentSort' => $sortField,
                'currentDir' => $sortDir,
                'totalUnitsCount' => $repository->countAll(),
                'activeUnitsCount' => $repository->countByActive(true),
                'rootUnitsCount' => count($repository->findRootUnits()),
            ]);
        }

        $parentId = $request->query->get('parent') ? (int) $request->query->get('parent') : null;
        $parent = $parentId ? $repository->find($parentId) : null;

        $breadcrumb = [];
        $walker = $parent;
        while ($walker) {
            array_unshift($breadcrumb, $walker);
            $walker = $walker->getParent();
        }

        $children = $repository->findChildrenOf($parentId);
        $rows = $this->buildRows($children, $repository, $userRepository);
        $rows = $this->sortRows($rows, $sortField, $sortDir);

        return $this->render('manage/geo/index.html.twig', [
            'rows' => $rows,
            'parent' => $parent,
            'breadcrumb' => $breadcrumb,
            'searchTerm' => null,
            'isSearchMode' => false,
            'currentSort' => $sortField,
            'currentDir' => $sortDir,
            'totalUnitsCount' => $repository->countAll(),
            'activeUnitsCount' => $repository->countByActive(true),
            'rootUnitsCount' => count($repository->findRootUnits()),
        ]);
    }

    private function sortRows(array $rows, string $field, string $dir): array
    {
        $allowed = ['name', 'typeLabel', 'active', 'childrenCount', 'countParticulier', 'countPro', 'countStore', 'countRelay'];
        if (!in_array($field, $allowed, true)) {
            $field = 'name';
        }
        $dir = strtoupper($dir) === 'DESC' ? -1 : 1;

        usort($rows, function ($a, $b) use ($field, $dir) {
            $valA = match ($field) {
                'name' => $a['unit']->getName(),
                'typeLabel' => $a['unit']->getTypeLabel() ?? '',
                'active' => $a['unit']->isActive() ? 1 : 0,
                default => $a[$field],
            };
            $valB = match ($field) {
                'name' => $b['unit']->getName(),
                'typeLabel' => $b['unit']->getTypeLabel() ?? '',
                'active' => $b['unit']->isActive() ? 1 : 0,
                default => $b[$field],
            };
            return $dir * ($valA <=> $valB);
        });

        return $rows;
    }

    private function buildRows(array $units, AdministrativeUnitRepository $repository, \App\Repository\UserRepository $userRepository): array
    {
        return array_map(function ($unit) use ($repository, $userRepository) {
            $descendantIds = array_map(fn ($u) => $u->getId(), $unit->getDescendantUnits());

            return [
                'unit' => $unit,
                'childrenCount' => $repository->countChildrenOf($unit->getId()),
                'countParticulier' => $userRepository->countByUnitsAndSellerType($descendantIds, null),
                'countPro' => $userRepository->countByUnitsAndSellerType($descendantIds, 'pro'),
                'countStore' => $userRepository->countByUnitsAndSellerType($descendantIds, 'store'),
                'countRelay' => $userRepository->countByUnitsAndSellerType($descendantIds, 'relay'),
            ];
        }, $units);
    }

    #[Route('/territoires/nouveau', name: 'manage_geo_new', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function new(Request $request, AdministrativeUnitRepository $repository): Response
    {
        $parentId = $request->query->get('parent') ? (int) $request->query->get('parent') : null;
        $parent = $parentId ? $repository->find($parentId) : null;

        return $this->render('manage/geo/form.html.twig', [
            'unit' => null,
            'parent' => $parent,
            'provinces' => $repository->findChildrenOf(null),
        ]);
    }

    #[Route('/territoires/nouveau', name: 'manage_geo_create', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function create(Request $request, AdministrativeUnitRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $parentId = $request->request->get('parent_id') ? (int) $request->request->get('parent_id') : null;
        $parent = $parentId ? $repository->find($parentId) : null;

        $unit = new AdministrativeUnit();
        $unit->setName($request->request->get('name'));
        $unit->setTypeLabel($request->request->get('type_label'));
        $unit->setLevel($parent ? $parent->getLevel() + 1 : 1);
        $unit->setParent($parent);
        $unit->setActive((bool) $request->request->get('active'));

        $slugger = new AsciiSlugger();
        $unit->setSlug(strtolower($slugger->slug($unit->getName())) . '-' . uniqid());

        $em->persist($unit);
        $em->flush();

        $this->addFlash('success', $unit->getName() . ' créé.');
        return $this->redirectToRoute('manage_geo_index', $parentId ? ['parent' => $parentId] : []);
    }

    #[Route('/territoires/{id}/modifier', name: 'manage_geo_edit', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function edit(AdministrativeUnit $unit): Response
    {
        return $this->render('manage/geo/form.html.twig', [
            'unit' => $unit,
            'parent' => $unit->getParent(),
        ]);
    }

    #[Route('/territoires/{id}/modifier', name: 'manage_geo_update', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function update(AdministrativeUnit $unit, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $unit->setName($request->request->get('name'));
        $unit->setTypeLabel($request->request->get('type_label'));
        $unit->setActive((bool) $request->request->get('active'));
        $em->flush();

        $this->addFlash('success', $unit->getName() . ' mis à jour.');
        $parentId = $unit->getParent()?->getId();
        return $this->redirectToRoute('manage_geo_index', $parentId ? ['parent' => $parentId] : []);
    }

    #[Route('/territoires/{id}/basculer', name: 'manage_geo_toggle', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function toggle(AdministrativeUnit $unit, EntityManagerInterface $em): RedirectResponse
    {
        $unit->setActive(!$unit->isActive());
        $em->flush();

        $this->addFlash('success', $unit->getName() . ' — ' . ($unit->isActive() ? 'activé' : 'désactivé') . '.');
        $parentId = $unit->getParent()?->getId();
        return $this->redirectToRoute('manage_geo_index', $parentId ? ['parent' => $parentId] : []);
    }

    #[Route('/territoires/{id}/supprimer', name: 'manage_geo_delete', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function delete(AdministrativeUnit $unit, EntityManagerInterface $em): RedirectResponse
    {
        $name = $unit->getName();
        $parentId = $unit->getParent()?->getId();

        $em->remove($unit); // Cascade DB : supprime aussi tous les descendants
        $em->flush();

        $this->addFlash('warning', $name . ' et tous ses descendants ont été supprimés.');
        return $this->redirectToRoute('manage_geo_index', $parentId ? ['parent' => $parentId] : []);
    }
}