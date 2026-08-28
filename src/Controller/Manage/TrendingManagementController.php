<?php

namespace App\Controller\Manage;

use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TrendingManagementController extends AbstractController
{
    #[Route('/tendances', name: 'manage_trending_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(Request $request, CategoryRepository $repository): Response
    {
        $searchTerm = $request->query->get('q');
        $searchResults = $searchTerm ? $repository->searchByName($searchTerm) : [];
        $pinned = $repository->findPinnedTrending();

        return $this->render('manage/trending/index.html.twig', [
            'pinned' => $pinned,
            'stats' => [
                'pinnedCount' => count($pinned),
                'totalCategories' => $repository->countAll(),
            ],
            'searchResults' => $searchResults,
            'searchTerm' => $searchTerm,
            'rootCategories' => $repository->findRootCategories(),
        ]);
    }

    #[Route('/tendances/epingler-cascade', name: 'manage_trending_pin_cascade', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function pinCascade(Request $request, CategoryRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $catId = (int) $request->request->get('catId');
        if ($catId) {
            $this->pin($catId, $repository, $em);
        }

        return $this->redirectToRoute('manage_trending_index');
    }

    #[Route('/tendances/{id}/epingler', name: 'manage_trending_pin', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function pin(int $id, CategoryRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $category = $repository->find($id);
        if ($category && !$category->isTrendingPinned()) {
            $maxPosition = 0;
            foreach ($repository->findPinnedTrending() as $pinnedCat) {
                $maxPosition = max($maxPosition, $pinnedCat->getTrendingPinnedPosition() ?? 0);
            }
            $category->setTrendingPinned(true);
            $category->setTrendingPinnedPosition($maxPosition + 1);
            $em->flush();
        }

        return $this->redirectToRoute('manage_trending_index');
    }

    #[Route('/tendances/{id}/detacher', name: 'manage_trending_unpin', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function unpin(int $id, CategoryRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $category = $repository->find($id);
        if ($category) {
            $category->setTrendingPinned(false);
            $category->setTrendingPinnedPosition(null);
            $em->flush();
        }

        return $this->redirectToRoute('manage_trending_index');
    }

    #[Route('/tendances/{id}/deplacer/{direction}', name: 'manage_trending_move', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function move(int $id, string $direction, CategoryRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $pinned = $repository->findPinnedTrending();
        $ids = array_map(fn ($c) => $c->getId(), $pinned);
        $index = array_search($id, $ids, true);

        if (false !== $index) {
            $swapWith = 'up' === $direction ? $index - 1 : $index + 1;
            if (isset($pinned[$swapWith])) {
                $posA = $pinned[$index]->getTrendingPinnedPosition();
                $posB = $pinned[$swapWith]->getTrendingPinnedPosition();
                $pinned[$index]->setTrendingPinnedPosition($posB);
                $pinned[$swapWith]->setTrendingPinnedPosition($posA);
                $em->flush();
            }
        }

        return $this->redirectToRoute('manage_trending_index');
    }
}