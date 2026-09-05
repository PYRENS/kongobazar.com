<?php

namespace App\Service;

use App\Entity\Category;
use App\Repository\AdvertisementRepository;
use App\Repository\CategoryRepository;
use App\Repository\RayonFlyoutColumnRepository;

/**
 * Résout le contenu du flyout (survol d'un rayon dans la sidebar "Top Rayons") :
 * - les colonnes affichées (RayonFlyoutColumn, choisies à n'importe quel niveau sous le rayon)
 * - repli automatique sur les 4 premières sous-catégories directes tant que rien n'est configuré
 * - la pub associée au rayon (relatedCategory + zoneKey 'rayon_flyout_ad'), et sa position (droite/bas)
 */
class RayonFlyoutResolver
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly AdvertisementRepository $advertisementRepository,
        private readonly RayonFlyoutColumnRepository $flyoutColumnRepository,
    ) {
    }

    public function resolve(Category $rayon): array
    {
        $configuredColumns = $this->flyoutColumnRepository->findByRayonOrdered($rayon);

        $columns = [];
        if (count($configuredColumns) > 0) {
            foreach ($configuredColumns as $column) {
                $columns[] = [
                    'category' => $column->getCategory(),
                    'items' => array_map(fn ($item) => $item->getCategory(), $column->getItems()->toArray()),
                ];
            }
        } else {
            // Repli : les 4 premières sous-catégories directes, chacune avec ses propres enfants comme items (comportement d'origine).
            foreach (array_slice($rayon->getChildren()->toArray(), 0, 4) as $child) {
                $columns[] = [
                    'category' => $child,
                    'items' => array_slice($child->getChildren()->toArray(), 0, 6),
                ];
            }
        }

        $ad = null;
        if ($rayon->getFlyoutAdPosition() === 'droite') {
            $ad = $this->advertisementRepository->findOneActiveByZoneAndCategory('rayon_flyout_ad_droite', $rayon, 'public');
        } elseif ($rayon->getFlyoutAdPosition() === 'bas') {
            $ad = $this->advertisementRepository->findOneActiveByZoneAndCategory('rayon_flyout_ad_bas', $rayon, 'public');
        }

        return [
            'columns' => $columns,
            'adPosition' => $rayon->getFlyoutAdPosition(),
            'ad' => $ad,
            'backgroundColor' => $rayon->getEffectiveThemeColor(),
        ];
    }
}