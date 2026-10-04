<?php

namespace App\Controller\Manage;

use App\Entity\SidebarFillerBanner;
use App\Repository\CategoryRepository;
use App\Repository\SidebarFillerBannerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SidebarFillerBannerController extends AbstractController
{
    private function buildFilteredBanners(Request $request, SidebarFillerBannerRepository $repository, \App\Repository\CategoryRepository $categoryRepository): array
    {
        $term = trim((string) $request->query->get('q', ''));
        $status = $request->query->get('status', '');
        $categoryId = $request->query->get('category') ? (int) $request->query->get('category') : null;
        $sort = $request->query->get('sort', 'id');
        $dir = strtoupper($request->query->get('dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $banners = $repository->findAllOrdered();

        if ($term !== '') {
            $banners = array_values(array_filter($banners, fn ($b) => str_contains(mb_strtolower($b->getTitle() ?? ''), mb_strtolower($term))));
        }
        if ($status === 'active') {
            $banners = array_values(array_filter($banners, fn ($b) => $b->isActive()));
        } elseif ($status === 'inactive') {
            $banners = array_values(array_filter($banners, fn ($b) => !$b->isActive()));
        }

        $selectedCategory = null;
        $ownBanners = [];
        $descendantBanners = [];

        if ($categoryId) {
            $selectedCategory = $categoryRepository->find($categoryId);
            $descendantIds = $selectedCategory ? array_map(fn ($c) => $c->getId(), $selectedCategory->getDescendantCategories()) : [];

            foreach ($banners as $banner) {
                $bannerCategoryIds = array_map(fn ($c) => $c->getId(), $banner->getCategories()->toArray());
                if (in_array($categoryId, $bannerCategoryIds, true)) {
                    $ownBanners[] = $banner;
                } elseif (array_intersect($bannerCategoryIds, $descendantIds)) {
                    $descendantBanners[] = $banner;
                }
            }
            $banners = array_merge($ownBanners, $descendantBanners);
        }

        $sortValue = function ($banner, $sort) {
            return match ($sort) {
                'title' => $banner->getTitle() ?? '',
                'active' => (int) $banner->isActive(),
                'targetUrl' => $banner->getTargetUrl() ?? '',
                'categories' => $banner->getCategories()->count() > 0 ? $banner->getCategories()->first()->getName() : '',
                default => $banner->getId(),
            };
        };
        $sortFn = function ($a, $b) use ($sort, $dir, $sortValue) {
            $cmp = $sortValue($a, $sort) <=> $sortValue($b, $sort);
            return 'ASC' === $dir ? $cmp : -$cmp;
        };
        usort($banners, $sortFn);
        usort($ownBanners, $sortFn);
        usort($descendantBanners, $sortFn);

        $perPage = max(1, (int) $request->query->get('perPage', 20));
        $page = max(1, (int) $request->query->get('page', 1));
        $total = count($banners);
        $pages = (int) max(1, ceil($total / $perPage));

        // La pagination ne s'applique que sur la liste "à plat" (pas de filtre catégorie) —
        // avec un filtre catégorie actif, on montre tout (propres + héritées), sans découper,
        // pour ne pas couper la séparation visuelle entre les deux groupes au milieu d'une page.
        if (!$selectedCategory) {
            $banners = array_slice($banners, ($page - 1) * $perPage, $perPage);
        }

        return [
            'banners' => $banners,
            'ownBanners' => $ownBanners,
            'descendantBanners' => $descendantBanners,
            'selectedCategory' => $selectedCategory,
            'term' => $term, 'status' => $status, 'categoryId' => $categoryId, 'sort' => $sort, 'dir' => $dir,
            'perPage' => $perPage, 'page' => $page, 'pages' => $pages, 'total' => $total,
        ];
    }

    #[Route('/parametres/bannieres-bouche-trou', name: 'manage_sidebar_filler_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(Request $request, SidebarFillerBannerRepository $repository, CategoryRepository $categoryRepository): Response
    {
        $built = $this->buildFilteredBanners($request, $repository, $categoryRepository);

        return $this->render('manage/sidebar_filler/index.html.twig', [
            'banners' => $built['banners'],
            'ownBanners' => $built['ownBanners'],
            'descendantBanners' => $built['descendantBanners'],
            'selectedCategory' => $built['selectedCategory'],
            'requiredWidth' => SidebarFillerBanner::REQUIRED_WIDTH,
            'rootCategories' => $categoryRepository->findRootCategories(),
            'searchTerm' => $built['term'],
            'currentStatus' => $built['status'],
            'currentCategory' => $built['categoryId'],
            'currentSort' => $built['sort'],
            'currentDir' => $built['dir'],
            'perPage' => $built['perPage'],
            'page' => $built['page'],
            'pages' => $built['pages'],
            'total' => $built['total'],
        ]);
    }

    #[Route('/parametres/bannieres-bouche-trou/liste-fragment', name: 'manage_sidebar_filler_index_fragment', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function indexFragment(Request $request, SidebarFillerBannerRepository $repository, CategoryRepository $categoryRepository): Response
    {
        $built = $this->buildFilteredBanners($request, $repository, $categoryRepository);

        return $this->json([
            'rowsHtml' => $this->renderView('manage/sidebar_filler/_index_rows.html.twig', [
                'banners' => $built['banners'],
                'ownBanners' => $built['ownBanners'],
                'descendantBanners' => $built['descendantBanners'],
                'selectedCategory' => $built['selectedCategory'],
            ]),
        ]);
    }
    #[Route('/parametres/bannieres-bouche-trou/ajouter', name: 'manage_sidebar_filler_new', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function new(CategoryRepository $categoryRepository): Response
    {
        return $this->render('manage/sidebar_filler/form.html.twig', [
            'banner' => null,
            'requiredWidth' => SidebarFillerBanner::REQUIRED_WIDTH,
            'rootCategories' => $categoryRepository->findRootCategories(),
        ]);
    }

    #[Route('/parametres/bannieres-bouche-trou/ajouter', name: 'manage_sidebar_filler_add', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $em, CategoryRepository $categoryRepository): RedirectResponse
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('image');
        if (!$file) {
            $this->addFlash('error', 'Une image est obligatoire.');
            return $this->redirectToRoute('manage_sidebar_filler_new');
        }

        $dimensions = @getimagesize($file->getPathname());
        $requiredWidth = SidebarFillerBanner::REQUIRED_WIDTH;
        if (!$dimensions || (int) $dimensions[0] !== $requiredWidth) {
            $this->addFlash('error', 'L\'image doit faire exactement ' . $requiredWidth . 'px de large (largeur reçue : ' . ($dimensions[0] ?? '?') . 'px). La hauteur est libre.');
            return $this->redirectToRoute('manage_sidebar_filler_new');
        }

        $banner = new SidebarFillerBanner();
        $banner->setTitle((string) $request->request->get('title', ''));
        $banner->setTargetUrl($request->request->get('target_url') ?: null);
        $banner->setOpenInNewTab((bool) $request->request->get('open_in_new_tab'));
        $banner->setImageFile($file);

        foreach ($request->request->all('categories') as $categoryId) {
            $category = $categoryRepository->find((int) $categoryId);
            if ($category) {
                $banner->addCategory($category);
            }
        }

        $em->persist($banner);
        $em->flush();

        $this->addFlash('success', 'Bannière ajoutée.');
        return $this->redirectToRoute('manage_sidebar_filler_index');
    }

    #[Route('/parametres/bannieres-bouche-trou/{id}/modifier', name: 'manage_sidebar_filler_edit', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function edit(SidebarFillerBanner $banner, CategoryRepository $categoryRepository): Response
    {
        return $this->render('manage/sidebar_filler/form.html.twig', [
            'banner' => $banner,
            'requiredWidth' => SidebarFillerBanner::REQUIRED_WIDTH,
            'rootCategories' => $categoryRepository->findRootCategories(),
        ]);
    }

    #[Route('/parametres/bannieres-bouche-trou/{id}/modifier', name: 'manage_sidebar_filler_update', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(SidebarFillerBanner $banner, Request $request, EntityManagerInterface $em, CategoryRepository $categoryRepository): RedirectResponse
    {
        $banner->setTitle((string) $request->request->get('title', ''));
        $banner->setTargetUrl($request->request->get('target_url') ?: null);
        $banner->setOpenInNewTab((bool) $request->request->get('open_in_new_tab'));

        /** @var UploadedFile|null $file */
        $file = $request->files->get('image');
        if ($file) {
            $dimensions = @getimagesize($file->getPathname());
            $requiredWidth = SidebarFillerBanner::REQUIRED_WIDTH;
            if (!$dimensions || (int) $dimensions[0] !== $requiredWidth) {
                $this->addFlash('error', 'L\'image doit faire exactement ' . $requiredWidth . 'px de large (largeur reçue : ' . ($dimensions[0] ?? '?') . 'px). La hauteur est libre.');
                return $this->redirectToRoute('manage_sidebar_filler_edit', ['id' => $banner->getId()]);
            }
            $banner->setImageFile($file);
        }

        foreach ($banner->getCategories()->toArray() as $existing) {
            $banner->removeCategory($existing);
        }
        foreach ($request->request->all('categories') as $categoryId) {
            $category = $categoryRepository->find((int) $categoryId);
            if ($category) {
                $banner->addCategory($category);
            }
        }

        $em->flush();

        $this->addFlash('success', 'Bannière modifiée.');
        return $this->redirectToRoute('manage_sidebar_filler_index');
    }

    #[Route('/parametres/bannieres-bouche-trou/{id}/supprimer', name: 'manage_sidebar_filler_remove', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function remove(SidebarFillerBanner $banner, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($banner);
        $em->flush();

        $this->addFlash('success', 'Bannière retirée.');
        return $this->redirectToRoute('manage_sidebar_filler_index');
    }

    #[Route('/parametres/bannieres-bouche-trou/{id}/basculer', name: 'manage_sidebar_filler_toggle', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(SidebarFillerBanner $banner, EntityManagerInterface $em): Response
    {
        $banner->setActive(!$banner->isActive());
        $em->flush();

        return $this->json(['ok' => true, 'active' => $banner->isActive()]);
    }
}