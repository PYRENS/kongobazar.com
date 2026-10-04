<?php

namespace App\Controller\Manage;

use App\Entity\HomeCategoryCarouselItem;
use App\Repository\CategoryRepository;
use App\Repository\HomeCategoryCarouselItemRepository;
use App\Repository\HomeCategoryCarouselSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Carrousel "Catégorie" de l'accueil (sans titre, juste sous le Hero) :
 * uniquement des catégories FEUILLES, cliquables vers /categorie/{slug}.
 * Liste vide = mode automatique (toutes les feuilles actives ayant des produits actifs).
 */
class HomeCategoryCarouselController extends AbstractController
{
    private const PER_PAGE_CHOICES = [10, 20, 50, 100];
    private const SORTS = ['position', 'name', 'path', 'products', 'active'];

    public function __construct(
        private readonly HomeCategoryCarouselItemRepository $itemRepository,
        private readonly HomeCategoryCarouselSettingRepository $settingRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** Liste filtrée / triée / paginée — partagée entre la page complète et le fragment AJAX. */
    private function buildList(Request $request): array
    {
        $term = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'position');
        if (!in_array($sort, self::SORTS, true)) {
            $sort = 'position';
        }
        $dir = 'DESC' === strtoupper((string) $request->query->get('dir', 'ASC')) ? 'DESC' : 'ASC';
        $perPage = (int) $request->query->get('perPage', 20);
        if (!in_array($perPage, self::PER_PAGE_CHOICES, true)) {
            $perPage = 20;
        }
        $page = max(1, (int) $request->query->get('page', 1));

        $items = $this->itemRepository->findAllOrdered();
        $counts = $this->categoryRepository->countActiveProductsByCategoryIds(
            array_map(fn (HomeCategoryCarouselItem $i) => $i->getCategory()->getId(), $items)
        );

        $rows = [];
        $lastIndex = count($items) - 1;
        foreach ($items as $index => $item) {
            $category = $item->getCategory();
            $rows[] = [
                'item' => $item,
                'category' => $category,
                'path' => $category->getParent() ? $category->getParent()->getFullPath() : '—',
                'productCount' => $counts[$category->getId()] ?? 0,
                'isFirst' => 0 === $index,
                'isLast' => $index === $lastIndex,
            ];
        }

        if ('' !== $term) {
            $needle = mb_strtolower($term);
            $rows = array_values(array_filter(
                $rows,
                fn ($r) => str_contains(mb_strtolower($r['category']->getFullPath()), $needle)
            ));
        }

        // Suggestions d'autocomplétion de la barre de recherche (noms des catégories trouvées).
        $suggestions = '' !== $term
            ? array_slice(array_values(array_unique(array_map(fn ($r) => $r['category']->getName(), $rows))), 0, 8)
            : [];

        usort($rows, function ($a, $b) use ($sort, $dir) {
            $value = fn ($r) => match ($sort) {
                'name' => mb_strtolower($r['category']->getName()),
                'path' => mb_strtolower($r['path']),
                'products' => $r['productCount'],
                'active' => (int) $r['item']->isActive(),
                default => $r['item']->getPosition(),
            };
            $cmp = $value($a) <=> $value($b);

            return 'ASC' === $dir ? $cmp : -$cmp;
        });

        $total = count($rows);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);

        return [
            'rows' => array_slice($rows, ($page - 1) * $perPage, $perPage),
            'total' => $total,
            'allCount' => count($items),
            'activeCount' => count(array_filter($items, fn (HomeCategoryCarouselItem $i) => $i->isActive())),
            'pages' => $pages,
            'page' => $page,
            'perPage' => $perPage,
            'term' => $term,
            'sort' => $sort,
            'dir' => $dir,
            'suggestions' => $suggestions,
            // Les flèches ↑↓ n'ont de sens que sur l'ordre réel, complet, non filtré.
            'canReorder' => 'position' === $sort && 'ASC' === $dir && '' === $term,
        ];
    }

    #[Route('/parametres/carrousel-categorie-accueil', name: 'manage_home_category_carousel_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $list = $this->buildList($request);

        return $this->render('manage/home_category_carousel/index.html.twig', $list + [
            'setting' => $this->settingRepository->getSingleton(),
            'autoLeafCount' => count($this->categoryRepository->findLeafCategoriesWithActiveProducts()),
            'perPageChoices' => self::PER_PAGE_CHOICES,
        ]);
    }

    #[Route('/parametres/carrousel-categorie-accueil/liste-fragment', name: 'manage_home_category_carousel_fragment', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function fragment(Request $request): JsonResponse
    {
        $list = $this->buildList($request);

        return $this->json([
            'rowsHtml' => $this->renderView('manage/home_category_carousel/_rows.html.twig', $list),
            'paginationHtml' => $this->renderView('manage/home_category_carousel/_pagination.html.twig', $list),
            'total' => $list['total'],
            'allCount' => $list['allCount'],
            'activeCount' => $list['activeCount'],
            'page' => $list['page'],
            'pages' => $list['pages'],
            'suggestions' => $list['suggestions'],
            'canReorder' => $list['canReorder'],
        ]);
    }

    #[Route('/parametres/carrousel-categorie-accueil/basculer', name: 'manage_home_category_carousel_toggle', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function toggleEnabled(): JsonResponse
    {
        $setting = $this->settingRepository->getSingleton();
        $setting->setEnabled(!$setting->isEnabled());
        $this->em->flush();

        return $this->json(['ok' => true, 'enabled' => $setting->isEnabled()]);
    }

    /**
     * Cascade (comme "Top Catégorie") : enfants actifs d'une catégorie, n'importe quel niveau.
     * Pour chaque enfant : a-t-il des sous-catégories (on descend) ou est-ce une feuille (ajoutable) ?
     */
    #[Route('/parametres/carrousel-categorie-accueil/categories-enfants', name: 'manage_home_category_carousel_children', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function childrenCategories(Request $request): JsonResponse
    {
        $parentId = $request->query->get('parent_id') ? (int) $request->query->get('parent_id') : null;
        $children = array_values(array_filter(
            $this->categoryRepository->findChildrenOf($parentId),
            fn ($c) => $c->isActive()
        ));

        $alreadyIds = array_map(fn (HomeCategoryCarouselItem $i) => $i->getCategory()->getId(), $this->itemRepository->findAllOrdered());
        $counts = $this->categoryRepository->countActiveProductsByCategoryIds(array_map(fn ($c) => $c->getId(), $children));

        return $this->json(['results' => array_map(fn ($c) => [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'hasChildren' => !$c->isLeaf(),
            'productCount' => $counts[$c->getId()] ?? 0,
            'alreadyAdded' => in_array($c->getId(), $alreadyIds, true),
        ], $children)]);
    }

    #[Route('/parametres/carrousel-categorie-accueil/ajouter', name: 'manage_home_category_carousel_add', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $category = $this->categoryRepository->find((int) $request->request->get('category_id'));

        if (!$category) {
            return $this->json(['ok' => false, 'message' => 'Catégorie introuvable.'], 404);
        }
        if (!$category->isLeaf()) {
            return $this->json(['ok' => false, 'message' => 'Seules les catégories sans sous-catégorie sont autorisées.'], 400);
        }
        if ($this->itemRepository->findOneBy(['category' => $category])) {
            return $this->json(['ok' => false, 'message' => 'Cette catégorie est déjà dans le carrousel.'], 400);
        }

        $item = new HomeCategoryCarouselItem();
        $item->setCategory($category);
        $item->setPosition($this->itemRepository->findNextPosition());
        $this->em->persist($item);
        $this->em->flush();

        return $this->json(['ok' => true, 'message' => '"' . $category->getName() . '" ajoutée au carrousel.']);
    }

    /** Active / désactive une catégorie du carrousel (reste dans la liste, mais masquée sur l'accueil). */
    #[Route('/parametres/carrousel-categorie-accueil/{id}/basculer', name: 'manage_home_category_carousel_item_toggle', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleItem(HomeCategoryCarouselItem $item): JsonResponse
    {
        $item->setActive(!$item->isActive());
        $this->em->flush();

        $activeCount = count(array_filter($this->itemRepository->findAllOrdered(), fn (HomeCategoryCarouselItem $i) => $i->isActive()));

        return $this->json(['ok' => true, 'active' => $item->isActive(), 'activeCount' => $activeCount]);
    }

    #[Route('/parametres/carrousel-categorie-accueil/{id}/supprimer', name: 'manage_home_category_carousel_remove', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function remove(HomeCategoryCarouselItem $item): JsonResponse
    {
        $name = $item->getCategory()->getName();
        $this->em->remove($item);
        $this->em->flush();

        return $this->json(['ok' => true, 'message' => '"' . $name . '" retirée du carrousel.']);
    }

    #[Route('/parametres/carrousel-categorie-accueil/{id}/deplacer/{direction}', name: 'manage_home_category_carousel_move', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function move(HomeCategoryCarouselItem $item, string $direction): JsonResponse
    {
        $items = $this->itemRepository->findAllOrdered();

        // Renumérote d'abord 0..n-1 (sécurité si des positions sont en doublon), puis échange.
        foreach ($items as $i => $it) {
            $it->setPosition($i);
        }

        $index = array_search($item->getId(), array_map(fn ($i) => $i->getId(), $items), true);
        $swapWith = 'up' === $direction ? $index - 1 : $index + 1;

        if (false !== $index && $swapWith >= 0 && $swapWith < count($items)) {
            $items[$index]->setPosition($swapWith);
            $items[$swapWith]->setPosition($index);
        }
        $this->em->flush();

        return $this->json(['ok' => true]);
    }
}