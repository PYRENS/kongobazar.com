<?php

namespace App\Service;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\CategoryViewLogRepository;

/**
 * Résout la liste des catégories à afficher dans le bandeau "Tendances" :
 * les catégories épinglées manuellement (admin) passent en premier, dans
 * l'ordre choisi, puis les places restantes se remplissent avec les
 * catégories réellement les plus visitées (7 derniers jours).
 */
class TrendingCategoryResolver
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly CategoryViewLogRepository $categoryViewLogRepository,
    ) {
    }

    public function resolve(int $limit = 8): array
    {
        $pinnedCategories = $this->categoryRepository->findPinnedTrending();

        $results = array_map(
            fn (Category $category) => ['category' => $category, 'visitCount' => null, 'pinned' => true],
            $pinnedCategories
        );

        if (count($results) >= $limit) {
            return array_slice($results, 0, $limit);
        }

        $pinnedIds = array_map(fn (Category $category) => $category->getId(), $pinnedCategories);
        $fromTraffic = $this->categoryViewLogRepository->findMostVisited(7, $limit);

        foreach ($fromTraffic as $entry) {
            if (count($results) >= $limit) {
                break;
            }
            if (in_array($entry['category']->getId(), $pinnedIds, true)) {
                continue; // déjà épinglée, pas de doublon
            }
            $entry['pinned'] = false;
            $results[] = $entry;
        }

        return $results;
    }
}