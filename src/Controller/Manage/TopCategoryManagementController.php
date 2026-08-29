<?php

namespace App\Controller\Manage;

use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TopCategoryManagementController extends AbstractController
{
    #[Route('/top-categorie', name: 'manage_top_category_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(CategoryRepository $repository): Response
    {
        $rayons = $repository->findAllRootCategoriesForTopCategoryAdmin();

        return $this->render('manage/top_category/index.html.twig', [
            'rayons' => $rayons,
            'stats' => [
                'total' => count($rayons),
                'inTop' => count(array_filter($rayons, fn ($r) => $r->isTopRayon())),
                'withAd' => count(array_filter($rayons, fn ($r) => $r->getFlyoutAdPosition())),
            ],
        ]);
    }

    #[Route('/top-categorie/{id}/basculer', name: 'manage_top_category_toggle', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleTopRayon(int $id, CategoryRepository $repository, EntityManagerInterface $em): Response
    {
        $rayon = $repository->find($id);
        if ($rayon) {
            $newState = !$rayon->isTopRayon();
            $rayon->setTopRayon($newState);

            if ($newState) {
                $maxPosition = 0;
                foreach ($repository->findTopRayons() as $sibling) {
                    $maxPosition = max($maxPosition, $sibling->getTopRayonPosition() ?? 0);
                }
                $rayon->setTopRayonPosition($maxPosition + 1);
            } else {
                $rayon->setTopRayonPosition(null);
            }

            $em->flush();
        }

        return $this->render('manage/top_category/_table.html.twig', [
            'rayons' => $repository->findAllRootCategoriesForTopCategoryAdmin(),
        ]);
    }

    #[Route('/top-categorie/{id}/deplacer/{direction}', name: 'manage_top_category_move', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function move(int $id, string $direction, CategoryRepository $repository, EntityManagerInterface $em): Response
    {
        $rayons = $repository->findTopRayons();

        foreach ($rayons as $i => $r) {
            if ($r->getTopRayonPosition() === null) {
                $r->setTopRayonPosition($i);
            }
        }

        $ids = array_map(fn ($c) => $c->getId(), $rayons);
        $index = array_search($id, $ids, true);

        if (false !== $index) {
            $swapWith = 'up' === $direction ? $index - 1 : $index + 1;
            if (isset($rayons[$swapWith])) {
                $posA = $rayons[$index]->getTopRayonPosition();
                $posB = $rayons[$swapWith]->getTopRayonPosition();
                $rayons[$index]->setTopRayonPosition($posB);
                $rayons[$swapWith]->setTopRayonPosition($posA);
            }
        }

        $em->flush();

        return $this->render('manage/top_category/_table.html.twig', [
            'rayons' => $repository->findAllRootCategoriesForTopCategoryAdmin(),
        ]);
    }

    #[Route('/top-categorie/{id}/mega-menu', name: 'manage_top_category_flyout', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function flyout(int $id, CategoryRepository $repository): Response
    {
        $rayon = $repository->find($id);
        if (!$rayon) {
            throw $this->createNotFoundException();
        }

        return $this->render('manage/top_category/flyout.html.twig', [
            'rayon' => $rayon,
            'children' => $rayon->getChildren(),
            'flyoutColumns' => $repository->findFlyoutFeaturedColumns($rayon),
        ]);
    }

    #[Route('/top-categorie/{id}/flyout-ad-position', name: 'manage_top_category_flyout_ad_position', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function setFlyoutAdPosition(int $id, Request $request, CategoryRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $rayon = $repository->find($id);
        if ($rayon) {
            $rayon->setFlyoutAdPosition($request->request->get('flyout_ad_position') ?: null);
            $em->flush();
        }

        return $this->redirectToRoute('manage_top_category_flyout', ['id' => $id]);
    }

    #[Route('/top-categorie/flyout-colonne/{id}/basculer', name: 'manage_top_category_flyout_column_toggle', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleFlyoutColumn(int $id, CategoryRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $child = $repository->find($id);
        if ($child) {
            $newState = !$child->isFlyoutColumnFeatured();
            $child->setFlyoutColumnFeatured($newState);

            if ($newState) {
                $maxPosition = 0;
                foreach ($repository->findFlyoutFeaturedColumns($child->getParent()) as $sibling) {
                    $maxPosition = max($maxPosition, $sibling->getFlyoutColumnPosition() ?? 0);
                }
                $child->setFlyoutColumnPosition($maxPosition + 1);
            } else {
                $child->setFlyoutColumnPosition(null);
            }

            $em->flush();
        }

        return $this->redirectToRoute('manage_top_category_flyout', ['id' => $child->getParent()->getId()]);
    }

    #[Route('/top-categorie/flyout-colonne/{id}/deplacer/{direction}', name: 'manage_top_category_flyout_column_move', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function moveFlyoutColumn(int $id, string $direction, CategoryRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $child = $repository->find($id);
        if (!$child) {
            throw $this->createNotFoundException();
        }

        $columns = $repository->findFlyoutFeaturedColumns($child->getParent());
        $ids = array_map(fn ($c) => $c->getId(), $columns);
        $index = array_search($id, $ids, true);

        if (false !== $index) {
            $swapWith = 'up' === $direction ? $index - 1 : $index + 1;
            if (isset($columns[$swapWith])) {
                $posA = $columns[$index]->getFlyoutColumnPosition();
                $posB = $columns[$swapWith]->getFlyoutColumnPosition();
                $columns[$index]->setFlyoutColumnPosition($posB);
                $columns[$swapWith]->setFlyoutColumnPosition($posA);
                $em->flush();
            }
        }

        return $this->redirectToRoute('manage_top_category_flyout', ['id' => $child->getParent()->getId()]);
    }
}