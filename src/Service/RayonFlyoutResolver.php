<?php

namespace App\Service;

use App\Entity\Category;
use App\Repository\AdvertisementRepository;
use App\Repository\CategoryRepository;

/**
 * Résout le contenu du flyout (survol d'un rayon dans la sidebar "Les Rayons") :
 * - les colonnes affichées (Category::$flyoutColumnFeatured / $flyoutColumnPosition sur les enfants directs)
 * - repli automatique sur les 4 premières colonnes / 6 premiers items tant que rien n'est épinglé manuellement
 * - la pub associée au rayon (relatedCategory + zoneKey 'rayon_flyout_ad'), et sa position (droite/bas)
 */
class RayonFlyoutResolver
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly AdvertisementRepository $advertisementRepository,
    ) {
    }

    public function resolve(Category $rayon): array
    {
        $featuredColumns = $this->categoryRepository->findFlyoutFeaturedColumns($rayon);

        $columns = count($featuredColumns) > 0
            ? $featuredColumns
            : array_slice($rayon->getChildren()->toArray(), 0, 4);

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
        ];
    }
}