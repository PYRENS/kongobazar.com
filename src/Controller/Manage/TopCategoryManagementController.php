<?php

namespace App\Controller\Manage;

use App\Entity\Category;
use App\Entity\RayonFlyoutColumn;
use App\Entity\RayonFlyoutColumnItem;
use App\Repository\CategoryRepository;
use App\Repository\RayonFlyoutColumnItemRepository;
use App\Repository\RayonFlyoutColumnRepository;
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
        $rayons = $repository->findTopRayons();

        return $this->render('manage/top_category/index.html.twig', [
            'rayons' => $rayons,
            'stats' => [
                'total' => count($rayons),
                'inTop' => count($rayons),
                'withAd' => count(array_filter($rayons, fn ($r) => $r->getFlyoutAdPosition())),
            ],
        ]);
    }

    /** Ajoute une catégorie (n'importe quel niveau, choisie par cascade) comme "Top Rayon". */
    #[Route('/top-categorie/ajouter', name: 'manage_top_category_add', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function add(Request $request, CategoryRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $categoryId = (int) $request->request->get('category_id');
        $category = $repository->find($categoryId);

        if (!$category) {
            $this->addFlash('error', 'Catégorie introuvable.');
            return $this->redirectToRoute('manage_top_category_index');
        }

        if ($category->isTopRayon()) {
            $this->addFlash('error', 'Cette catégorie est déjà dans Top Rayons.');
            return $this->redirectToRoute('manage_top_category_index');
        }

        $maxPosition = 0;
        foreach ($repository->findTopRayons() as $sibling) {
            $maxPosition = max($maxPosition, $sibling->getTopRayonPosition() ?? 0);
        }
        $category->setTopRayon(true);
        $category->setTopRayonPosition($maxPosition + 1);
        $em->flush();

        $this->addFlash('success', 'Catégorie ajoutée à Top Rayons.');
        return $this->redirectToRoute('manage_top_category_index');
    }

    #[Route('/top-categorie/{id}/basculer', name: 'manage_top_category_toggle', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleTopRayon(int $id, CategoryRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $rayon = $repository->find($id);
        if ($rayon) {
            $rayon->setTopRayon(false);
            $rayon->setTopRayonPosition(null);
            $em->flush();
        }

        return $this->redirectToRoute('manage_top_category_index');
    }

    #[Route('/top-categorie/{id}/deplacer/{direction}', name: 'manage_top_category_move', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function move(int $id, string $direction, CategoryRepository $repository, EntityManagerInterface $em): Response
    {
        $rayons = $repository->findTopRayons();
        $ids = array_map(fn ($c) => $c->getId(), $rayons);
        $index = array_search($id, $ids, true);

        if (false !== $index) {
            $swapWith = 'up' === $direction ? $index - 1 : $index + 1;
            if (isset($rayons[$swapWith])) {
                $posA = $rayons[$index]->getTopRayonPosition();
                $posB = $rayons[$swapWith]->getTopRayonPosition();
                $rayons[$index]->setTopRayonPosition($posB);
                $rayons[$swapWith]->setTopRayonPosition($posA);
                $em->flush();
            }
        }

        return $this->redirectToRoute('manage_top_category_index');
    }

    #[Route('/top-categorie/{id}/mega-menu', name: 'manage_top_category_flyout', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function flyout(int $id, CategoryRepository $repository, RayonFlyoutColumnRepository $flyoutColumnRepository, \App\Repository\AdvertisementRepository $advertisementRepository): Response
    {
        $rayon = $repository->find($id);
        if (!$rayon) {
            throw $this->createNotFoundException();
        }

        $currentAd = null;
        if ($rayon->getFlyoutAdPosition() === 'droite') {
            $currentAd = $advertisementRepository->findOneActiveByZoneAndCategory('rayon_flyout_ad_droite', $rayon, 'public');
        } elseif ($rayon->getFlyoutAdPosition() === 'bas') {
            $currentAd = $advertisementRepository->findOneActiveByZoneAndCategory('rayon_flyout_ad_bas', $rayon, 'public');
        }

        return $this->render('manage/top_category/flyout.html.twig', [
            'rayon' => $rayon,
            'flyoutColumns' => $flyoutColumnRepository->findByRayonOrdered($rayon),
            'currentAd' => $currentAd,
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

    /** Ajoute une colonne au flyout — cascade à n'importe quel niveau sous le rayon. */
    #[Route('/top-categorie/{id}/flyout-colonne/ajouter', name: 'manage_top_category_flyout_column_add', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addFlyoutColumn(int $id, Request $request, CategoryRepository $repository, RayonFlyoutColumnRepository $flyoutColumnRepository, EntityManagerInterface $em): RedirectResponse
    {
        $rayon = $repository->find($id);
        if (!$rayon) {
            throw $this->createNotFoundException();
        }

        $categoryId = (int) $request->request->get('category_id');
        $category = $repository->find($categoryId);

        if ($category) {
            $alreadyThere = false;
            foreach ($flyoutColumnRepository->findByRayonOrdered($rayon) as $existing) {
                if ($existing->getCategory()->getId() === $category->getId()) {
                    $alreadyThere = true;
                    break;
                }
            }
            if ($alreadyThere) {
                $this->addFlash('error', 'Cette catégorie est déjà une colonne de ce flyout.');
            } else {
                $column = new RayonFlyoutColumn();
                $column->setRayon($rayon);
                $column->setCategory($category);
                $column->setPosition($flyoutColumnRepository->findNextPosition($rayon));
                $em->persist($column);
                $em->flush();
                $this->addFlash('success', 'Colonne ajoutée.');
            }
        }

        return $this->redirectToRoute('manage_top_category_flyout', ['id' => $id]);
    }

    #[Route('/top-categorie/flyout-colonne/{id}/supprimer', name: 'manage_top_category_flyout_column_remove', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function removeFlyoutColumn(RayonFlyoutColumn $column, EntityManagerInterface $em): RedirectResponse
    {
        $rayonId = $column->getRayon()->getId();
        $em->remove($column);
        $em->flush();

        return $this->redirectToRoute('manage_top_category_flyout', ['id' => $rayonId]);
    }

    #[Route('/top-categorie/flyout-colonne/{id}/deplacer/{direction}', name: 'manage_top_category_flyout_column_move', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function moveFlyoutColumn(RayonFlyoutColumn $column, string $direction, RayonFlyoutColumnRepository $flyoutColumnRepository, EntityManagerInterface $em): RedirectResponse
    {
        $columns = $flyoutColumnRepository->findByRayonOrdered($column->getRayon());
        $ids = array_map(fn ($c) => $c->getId(), $columns);
        $index = array_search($column->getId(), $ids, true);

        if (false !== $index) {
            $swapWith = 'up' === $direction ? $index - 1 : $index + 1;
            if (isset($columns[$swapWith])) {
                $posA = $columns[$index]->getPosition();
                $posB = $columns[$swapWith]->getPosition();
                $columns[$index]->setPosition($posB);
                $columns[$swapWith]->setPosition($posA);
                $em->flush();
            }
        }

        return $this->redirectToRoute('manage_top_category_flyout', ['id' => $column->getRayon()->getId()]);
    }

    /** Ajoute un item (lien) à l'intérieur d'une colonne — cascade à n'importe quel niveau. */
    #[Route('/top-categorie/flyout-colonne/{id}/item/ajouter', name: 'manage_top_category_flyout_column_item_add', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addFlyoutColumnItem(RayonFlyoutColumn $column, Request $request, CategoryRepository $repository, RayonFlyoutColumnItemRepository $itemRepository, EntityManagerInterface $em): RedirectResponse
    {
        $categoryId = (int) $request->request->get('category_id');
        $category = $repository->find($categoryId);

        if ($category) {
            $alreadyThere = false;
            foreach ($column->getItems() as $existing) {
                if ($existing->getCategory()->getId() === $category->getId()) {
                    $alreadyThere = true;
                    break;
                }
            }
            if ($alreadyThere) {
                $this->addFlash('error', 'Cette catégorie est déjà un item de cette colonne.');
            } else {
                $item = new RayonFlyoutColumnItem();
                $item->setColumn($column);
                $item->setCategory($category);
                $item->setPosition($itemRepository->findNextPosition($column));
                $em->persist($item);
                $em->flush();
                $this->addFlash('success', 'Item ajouté.');
            }
        }

        return $this->redirectToRoute('manage_top_category_flyout', ['id' => $column->getRayon()->getId()]);
    }

    #[Route('/top-categorie/flyout-item/{id}/supprimer', name: 'manage_top_category_flyout_column_item_remove', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function removeFlyoutColumnItem(RayonFlyoutColumnItem $item, EntityManagerInterface $em): RedirectResponse
    {
        $rayonId = $item->getColumn()->getRayon()->getId();
        $em->remove($item);
        $em->flush();

        return $this->redirectToRoute('manage_top_category_flyout', ['id' => $rayonId]);
    }

    #[Route('/top-categorie/flyout-item/{id}/deplacer/{direction}', name: 'manage_top_category_flyout_column_item_move', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function moveFlyoutColumnItem(RayonFlyoutColumnItem $item, string $direction, RayonFlyoutColumnItemRepository $itemRepository, EntityManagerInterface $em): RedirectResponse
    {
        $items = $itemRepository->findByColumnOrdered($item->getColumn());
        $ids = array_map(fn ($i) => $i->getId(), $items);
        $index = array_search($item->getId(), $ids, true);

        if (false !== $index) {
            $swapWith = 'up' === $direction ? $index - 1 : $index + 1;
            if (isset($items[$swapWith])) {
                $posA = $items[$index]->getPosition();
                $posB = $items[$swapWith]->getPosition();
                $items[$index]->setPosition($posB);
                $items[$swapWith]->setPosition($posA);
                $em->flush();
            }
        }

        return $this->redirectToRoute('manage_top_category_flyout', ['id' => $item->getColumn()->getRayon()->getId()]);
    }

    /** Cascade générique — enfants d'une catégorie, n'importe quel niveau. */
    #[Route('/top-categorie/categories-enfants', name: 'manage_top_category_children_cascade', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function childrenCategories(Request $request, CategoryRepository $categoryRepository): Response
    {
        $parentId = $request->query->get('parent_id') ? (int) $request->query->get('parent_id') : null;
        $children = $categoryRepository->findChildrenOf($parentId);

        return $this->json(['results' => array_map(fn (Category $c) => [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'hasChildren' => count($categoryRepository->findChildrenOf($c->getId())) > 0,
        ], $children)]);
    }
}
