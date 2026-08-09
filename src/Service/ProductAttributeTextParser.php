<?php

namespace App\Service;

class ProductAttributeTextParser
{
    /** @return array{items: array, unrecognizedLines: array} */
    public function parse(string $rawText): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $rawText)), fn ($l) => '' !== $l));

        $items = [];
        $unrecognized = [];

        foreach ($lines as $line) {
            if (!str_contains($line, ':')) {
                $unrecognized[] = $line;
                continue;
            }

            [$left, $right] = explode(':', $line, 2);
            $left = trim($left);
            $value = trim($right);

            if ('' === $left || '' === $value) {
                $unrecognized[] = $line;
                continue;
            }

            $unit = null;
            if (preg_match('/\[([^\]]+)\]/u', $left, $m)) {
                $unit = trim($m[1]);
                $left = trim((string) str_replace($m[0], '', $left));
            }

            $items[] = ['name' => $left, 'unit' => $unit, 'value' => $value];
        }

        return ['items' => $items, 'unrecognizedLines' => $unrecognized];
    }
}