<?php

namespace App\Twig;

use App\Service\PriceFormatter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

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

    public function getFunctions(): array
    {
        return [new TwigFunction('usd_to_cdf_rate', [$this->priceFormatter, 'getUsdToCdfRate'])];
    }
}