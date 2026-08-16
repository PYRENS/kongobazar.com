<?php

namespace App\Service;

use App\Repository\CategoryRepository;

/**
 * Résout le contenu du méga-menu "Catalogue" :
 * - les rayons affichés et leur ordre (Category::$megaMenuVisible / $megaMenuPosition)
 * - les sous-catégories affichées dans chaque colonne (Category::$megaMenuChildFeatured / $megaMenuChildPosition)
 *
 * Tant qu'aucune sous-catégorie n'a été épinglée manuellement pour un rayon donné,
 * on retombe sur le comportement historique (les 5 premières par position),
 * pour ne rien casser avant que l'admin configure quoi que ce soit.
 */
class MegaMenuResolver
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {
    }

    public function resolve(): array
    {
        $rayons = $this->categoryRepository->findMegaMenuRayons();

        return array_map(function ($rayon) {
            $featuredChildren = $this->categoryRepository->findMegaMenuFeaturedChildren($rayon);

            $children = count($featuredChildren) > 0
                ? $featuredChildren
                : array_slice($rayon->getChildren()->toArray(), 0, 5);

            return [
                'rayon' => $rayon,
                'children' => $children,
            ];
        }, $rayons);
    }
}