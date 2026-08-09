<?php

namespace App\Service;

class VehicleImportParser
{
    private const NOISE_WORDS = ['autre', 'rechercher', 'recherche', 'choisissez une motorisation'];

    public function __construct(private readonly VehicleTextNormalizer $normalizer)
    {
    }

    public function parse(string $rawText, string $type): array
    {
        $lines = array_values(array_filter(
            array_map(
                fn ($l) => preg_replace('/^[\*\-\•]\s*/', '', trim($l)),
                preg_split('/\r\n|\r|\n/', $rawText)
            ),
            fn ($l) => '' !== $l
        ));

        if (count($lines) < 2) {
            return ['error' => 'Texte insuffisant : il faut au moins la ligne Marque et la ligne Modèle.'];
        }

        $brandName = array_shift($lines);
        $modelLine = array_shift($lines);

        $modelParsed = $this->parseModelLine($modelLine, $type);
        if (null === $modelParsed) {
            $expected = 'auto' === $type
                ? '"Modèle? Variante (MM.YYYY - MM.YYYY|...)"'
                : '"Modèle (MM.YYYY - MM.YYYY|...)"';

            return ['error' => 'Impossible de lire la ligne du modèle : "' . $modelLine . '". Format attendu : ' . $expected];
        }

        $rows = [];
        $currentFuelName = null;
        $sawFirstRealMotorisation = false;
        $unrecognized = [];

        $i = 0;
        $count = count($lines);
        while ($i < $count) {
            $line = $lines[$i];
            $normalized = $this->normalizer->normalize($line);

            if (in_array($normalized, self::NOISE_WORDS, true)) {
                $i++;
                continue;
            }

            if (preg_match('/^(.*?)\(\s*(\d+)\s*KW\s*\/\s*(\d+)\s*CV\s*\)(.*)$/i', $line, $m)) {
                $label = trim($m[1]);
                $powerKw = (int) $m[2];
                $powerCv = (int) $m[3];
                $trailing = trim($m[4]);

                $displacementCc = null;
                if (preg_match('/(\d+)\s*ccm/i', $label, $ccMatch)) {
                    $displacementCc = (int) $ccMatch[1];
                    $label = trim(str_replace($ccMatch[0], '', $label));
                }

                // Cas "aperçu" : période collée sur la même ligne, avant toute vraie motorisation -> bruit ignoré
                if ('' !== $trailing && !$sawFirstRealMotorisation && null !== $this->parsePeriod($trailing)) {
                    $i++;
                    continue;
                }

                $period = '' !== $trailing ? $this->parsePeriod($trailing) : null;

                if (null === $period) {
                    $j = $i + 1;
                    while ($j < $count) {
                        $candidate = $lines[$j];
                        if (in_array($this->normalizer->normalize($candidate), self::NOISE_WORDS, true)) {
                            $j++;
                            continue;
                        }
                        $period = $this->parsePeriod($candidate);
                        if (null !== $period) {
                            $i = $j;
                        }
                        break;
                    }
                }

                $sawFirstRealMotorisation = true;

                $rows[] = [
                    'fuelName' => $currentFuelName,
                    'label' => $label,
                    'powerKw' => $powerKw,
                    'powerCv' => $powerCv,
                    'displacementCc' => $displacementCc,
                    'periodBegin' => $period['begin'] ?? null,
                    'periodEnd' => $period['end'] ?? null,
                ];

                $i++;
                continue;
            }

            // Tout ce qui n'est ni une motorisation (déjà traitée ci-dessus), ni du bruit,
            // ni une période isolée => en-tête carburant. On ne se fie plus à l'absence
            // de parenthèses, puisque des noms de carburant en contiennent (ex: "(GPL)").
            if (null === $this->parsePeriod($line) && mb_strlen($line) <= 60) {
                $currentFuelName = $line;
                $i++;
                continue;
            }
            $unrecognized[] = $line;
            $i++;
        }

        return [
            'brandName' => $brandName,
            'modelName' => $modelParsed['modelName'],
            'variantName' => $modelParsed['variantName'],
            'modelPeriodBegin' => $modelParsed['periodBegin'],
            'modelPeriodEnd' => $modelParsed['periodEnd'],
            'rows' => $rows,
            'unrecognizedLines' => $unrecognized,
        ];
    }

    private function parseModelLine(string $line, string $type): ?array
    {
        if ('auto' === $type) {
            if (!str_contains($line, '?')) {
                return null;
            }
            [$before, $after] = array_map('trim', explode('?', $line, 2));

            if (!preg_match('/^(.*?)\s*\(([^)]+)\)\s*$/', $after, $m)) {
                return null;
            }

            $period = $this->parsePeriod(trim($m[2]));
            if (null === $period) {
                return null;
            }

            return [
                'modelName' => $before,
                'variantName' => trim($m[1]) ?: null,
                'periodBegin' => $period['begin'],
                'periodEnd' => $period['end'],
            ];
        }

        if (!preg_match('/^(.*?)\s*\(([^)]+)\)\s*$/', $line, $m)) {
            return null;
        }

        $period = $this->parsePeriod(trim($m[2]));
        if (null === $period) {
            return null;
        }

        return [
            'modelName' => trim($m[1]),
            'variantName' => null,
            'periodBegin' => $period['begin'],
            'periodEnd' => $period['end'],
        ];
    }

    private function parsePeriod(string $raw): ?array
    {
        $raw = trim($raw);
        if (!preg_match('/^(\d{2})\.(\d{4})\s*-\s*(\.\.\.|(\d{2})\.(\d{4}))$/', $raw, $m)) {
            return null;
        }

        $begin = ['month' => $m[1], 'year' => (int) $m[2]];
        $end = '...' !== $m[3] ? ['month' => $m[4], 'year' => (int) $m[5]] : null;

        return ['begin' => $begin, 'end' => $end];
    }
}