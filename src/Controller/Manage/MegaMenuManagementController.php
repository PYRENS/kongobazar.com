<?php

namespace App\Controller\Manage;

use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MegaMenuManagementController extends AbstractController
{
    #[Route('/menu-catalogue', name: 'manage_mega_menu_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(CategoryRepository $repository): Response
    {
        return $this->render('manage/mega_menu/index.html.twig', [
            'rayons' => $repository->findAllRootCategoriesForMegaMenuAdmin(),
        ]);
    }

    #[Route('/menu-catalogue/{id}/basculer', name: 'manage_mega_menu_toggle', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleVisible(int $id, CategoryRepository $repository, EntityManagerInterface $em): Response
    {
        $rayon = $repository->find($id);
        if ($rayon) {
            $rayon->setMegaMenuVisible(!$rayon->isMegaMenuVisible());
            $em->flush();
        }

        return $this->render('manage/mega_menu/_table.html.twig', [
            'rayons' => $repository->findAllRootCategoriesForMegaMenuAdmin(),
        ]);
    }

    #[Route('/menu-catalogue/{id}/deplacer/{direction}', name: 'manage_mega_menu_move', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function move(int $id, string $direction, CategoryRepository $repository, EntityManagerInterface $em): Response
    {
        $rayons = $repository->findAllRootCategoriesForMegaMenuAdmin();

        foreach ($rayons as $i => $r) {
            if ($r->getMegaMenuPosition() === null) {
                $r->setMegaMenuPosition($i);
            }
        }

        $ids = array_map(fn ($c) => $c->getId(), $rayons);
        $index = array_search($id, $ids, true);

        if (false !== $index) {
            $swapWith = 'up' === $direction ? $index - 1 : $index + 1;
            if (isset($rayons[$swapWith])) {
                $posA = $rayons[$index]->getMegaMenuPosition();
                $posB = $rayons[$swapWith]->getMegaMenuPosition();
                $rayons[$index]->setMegaMenuPosition($posB);
                $rayons[$swapWith]->setMegaMenuPosition($posA);
            }
        }

        $em->flush();

        return $this->render('manage/mega_menu/_table.html.twig', [
            'rayons' => $repository->findAllRootCategoriesForMegaMenuAdmin(),
        ]);
    }

    #[Route('/menu-catalogue/{id}/sous-categories', name: 'manage_mega_menu_children', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function children(int $id, CategoryRepository $repository): Response
    {
        $rayon = $repository->find($id);
        if (!$rayon) {
            throw $this->createNotFoundException();
        }

        return $this->render('manage/mega_menu/children.html.twig', [
            'rayon' => $rayon,
            'children' => $rayon->getChildren(),
            'featured' => $repository->findMegaMenuFeaturedChildren($rayon),
        ]);
    }

    #[Route('/menu-catalogue/sous-categorie/{id}/basculer', name: 'manage_mega_menu_child_toggle', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleChildFeatured(int $id, CategoryRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $child = $repository->find($id);
        if ($child) {
            $newState = !$child->isMegaMenuChildFeatured();
            $child->setMegaMenuChildFeatured($newState);

            if ($newState) {
                $maxPosition = 0;
                foreach ($repository->findMegaMenuFeaturedChildren($child->getParent()) as $sibling) {
                    $maxPosition = max($maxPosition, $sibling->getMegaMenuChildPosition() ?? 0);
                }
                $child->setMegaMenuChildPosition($maxPosition + 1);
            } else {
                $child->setMegaMenuChildPosition(null);
            }

            $em->flush();
        }

        return $this->redirectToRoute('manage_mega_menu_children', ['id' => $child->getParent()->getId()]);
    }

    #[Route('/menu-catalogue/sous-categorie/{id}/deplacer/{direction}', name: 'manage_mega_menu_child_move', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function moveChild(int $id, string $direction, CategoryRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $child = $repository->find($id);
        if (!$child) {
            throw $this->createNotFoundException();
        }

        $featured = $repository->findMegaMenuFeaturedChildren($child->getParent());
        $ids = array_map(fn ($c) => $c->getId(), $featured);
        $index = array_search($id, $ids, true);

        if (false !== $index) {
            $swapWith = 'up' === $direction ? $index - 1 : $index + 1;
            if (isset($featured[$swapWith])) {
                $posA = $featured[$index]->getMegaMenuChildPosition();
                $posB = $featured[$swapWith]->getMegaMenuChildPosition();
                $featured[$index]->setMegaMenuChildPosition($posB);
                $featured[$swapWith]->setMegaMenuChildPosition($posA);
                $em->flush();
            }
        }

        return $this->redirectToRoute('manage_mega_menu_children', ['id' => $child->getParent()->getId()]);
    }
}