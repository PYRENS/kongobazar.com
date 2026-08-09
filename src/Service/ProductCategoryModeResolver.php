<?php

namespace App\Service;

use App\Entity\Category;

class ProductCategoryModeResolver
{
    /**
     * Détecte la "verticale" applicable à une catégorie, en remontant son arbre d'ancêtres.
     *
     * @return array{mode: string, vehicleType: ?string, isRental: bool}
     */
    public function resolve(Category $category): array
    {
        $slugs = array_map(fn (Category $c) => $c->getSlug(), $category->getAncestors());

        if (in_array('auto-moto', $slugs, true)) {
            $vehicleType = in_array('moto', $slugs, true) ? 'moto' : 'auto';
            $mode = in_array('offre', $slugs, true) ? 'vehicle_offer' : 'vehicle_part';

            return ['mode' => $mode, 'vehicleType' => $vehicleType, 'isRental' => false];
        }

        if (in_array('immobilier', $slugs, true)) {
            return ['mode' => 'property', 'vehicleType' => null, 'isRental' => in_array('location', $slugs, true)];
        }

        return ['mode' => 'generic', 'vehicleType' => null, 'isRental' => false];
    }
}