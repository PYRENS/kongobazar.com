<?php

namespace App\Twig;

use App\Service\SeoResolver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SeoExtension extends AbstractExtension
{
    public function __construct(private readonly SeoResolver $resolver)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('seo', [$this, 'resolve']),
        ];
    }

    public function resolve(string $entityType, ?int $entityId = null, ?string $pageKey = null, array $defaults = []): array
    {
        return $this->resolver->resolve($entityType, $entityId, $pageKey, $defaults);
    }
}
