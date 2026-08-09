<?php

namespace App\Service;

class PartCompatibilityParser
{
    private const NOISE_WORDS = ['autre', 'rechercher', 'recherche', 'choisissez une motorisation'];

    public function __construct(private readonly VehicleTextNormalizer $normalizer)
    {
    }

    /**
     * @return array{blocks: array, unrecognizedLines: array}
     */
    public function parse(string $rawText): array
    {
        $lines = array_values(array_filter(
            array_map(
                fn ($l) => preg_replace('/^[\*\-\•]\s*/', '', trim($l)),
                preg_split('/\r\n|\r|\n/', $rawText)
            ),
            fn ($l) => '' !== $l
        ));

        $blocks = [];
        $unrecognized = [];
        $current = null;

        foreach ($lines as $line) {
            $normalized = $this->normalizer->normalize($line);
            if (in_array($normalized, self::NOISE_WORDS, true)) {
                continue;
            }

            if ($engine = $this->parseEngineLine($line)) {
                if (null === $current) {
                    $unrecognized[] = $line; // motorisation sans en-tête précédent
                    continue;
                }
                $current['engines'][] = $engine;
                continue;
            }

            if ($header = $this->parseHeaderLine($line)) {
                if (null !== $current) {
                    $blocks[] = $current;
                }
                $current = $header + ['engines' => []];
                continue;
            }

            $unrecognized[] = $line;
        }

        if (null !== $current) {
            $blocks[] = $current;
        }

        return ['blocks' => $blocks, 'unrecognizedLines' => $unrecognized];
    }

    private function parseHeaderLine(string $line): ?array
    {
        if (!preg_match('/^(.*?)\(Année de construction\s+(\d{2})\.(\d{4})\s*-\s*(\.\.\.|(\d{2})\.(\d{4}))\)\s*$/u', $line, $m)) {
            return null;
        }

        return [
            'headerPrefix' => trim($m[1]),
            'periodBegin' => ['month' => $m[2], 'year' => (int) $m[3]],
            'periodEnd' => '...' !== $m[4] ? ['month' => $m[5], 'year' => (int) $m[6]] : null,
        ];
    }

    private function parseEngineLine(string $line): ?array
    {
        if (!preg_match(
            '/^(.+?),\s*Année de construction\s+(\d{2})\.(\d{4})\s*-\s*(\.\.\.|(\d{2})\.(\d{4})),\s*(\d+)\s*ccm,\s*(\d+)\s*CV\s*$/u',
            $line,
            $m
        )) {
            return null;
        }

        return [
            'label' => trim($m[1]),
            'periodBegin' => ['month' => $m[2], 'year' => (int) $m[3]],
            'periodEnd' => '...' !== $m[4] ? ['month' => $m[5], 'year' => (int) $m[6]] : null,
            'displacementCc' => (int) $m[7],
            'powerCv' => (int) $m[8],
        ];
    }
}