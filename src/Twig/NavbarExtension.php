<?php

namespace App\Twig;

use App\Service\NavbarItemProvider;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NavbarExtension extends AbstractExtension
{
    public function __construct(private readonly NavbarItemProvider $navbarItemProvider)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('navbar_items', [$this->navbarItemProvider, 'getPublicItems']),
            new TwigFunction('navbar_mobile_items', [$this->navbarItemProvider, 'getMobileItems']),
        ];
    }
}