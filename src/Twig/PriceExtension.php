<?php

namespace App\Twig;

use App\Service\PriceFormatter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Filtre |price : affiche un montant dans la devise choisie par le visiteur.
 * Usage : {{ product.displayCurrentPrice|price(product.currency) }}
 */
class PriceExtension extends AbstractExtension
{
    public function __construct(private readonly PriceFormatter $priceFormatter)
    {
    }

    public function getFilters(): array
    {
        return [new TwigFilter('price', [$this->priceFormatter, 'display'])];
    }
}