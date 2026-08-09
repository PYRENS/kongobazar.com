<?php

namespace App\Service;

use Symfony\Component\String\Slugger\AsciiSlugger;

class VehicleTextNormalizer
{
    private AsciiSlugger $slugger;

    public function __construct()
    {
        $this->slugger = new AsciiSlugger();
    }

    /** Sans accents, minuscules, séparateurs uniformisés — pour comparaison uniquement. */
    public function normalize(?string $value): string
    {
        if (null === $value || '' === trim($value)) {
            return '';
        }

        return strtolower((string) $this->slugger->slug($value));
    }

    public function equals(?string $a, ?string $b): bool
    {
        $normA = $this->normalize($a);

        return '' !== $normA && $normA === $this->normalize($b);
    }
}