<?php

namespace App\Service;

use App\Entity\Brand;
use App\Entity\VehicleEngine;
use App\Entity\VehicleModel;
use App\Repository\BrandRepository;
use App\Repository\VehicleEngineRepository;
use App\Repository\VehicleModelRepository;
use App\Repository\VehicleVariantRepository;

class PartCompatibilityReconciler
{
    public function __construct(
        private readonly VehicleTextNormalizer $normalizer,
        private readonly BrandRepository $brandRepository,
        private readonly VehicleModelRepository $modelRepository,
        private readonly VehicleVariantRepository $variantRepository,
        private readonly VehicleEngineRepository $engineRepository,
    ) {
    }

    public function reconcile(array $parsed): array
    {
        $brands = $this->brandRepository->findAll();
        $results = [];

        foreach ($parsed['blocks'] as $block) {
            $results[] = $this->reconcileBlock($block, $brands);
        }

        return ['blocks' => $results, 'unrecognizedLines' => $parsed['unrecognizedLines']];
    }

    private function reconcileBlock(array $block, array $brands): array
    {
        $prefix = $block['headerPrefix'];
        $brand = $this->matchBrandPrefix($prefix, $brands);

        $modelName = null;
        $variantName = null;
        $model = null;

        if ($brand) {
            $remainder = trim(mb_substr($prefix, mb_strlen($brand->getName())));
            $remainder = ltrim($remainder, " \t-");
            $models = $this->modelRepository->findByBrand($brand->getId());
            $matched = $this->matchModelPrefix($remainder, $models);

            if ($matched) {
                $model = $matched['model'];
                $modelName = $model->getName();
                $variantName = trim($matched['remainder']) ?: null;
            } else {
                // découpage par défaut (1er mot = modèle, reste = variante) — modifiable par l'admin dans l'écran de relecture
                $parts = explode(' ', $remainder, 2);
                $modelName = $parts[0] ?? $remainder;
                $variantName = trim($parts[1] ?? '') ?: null;
            }
        }

        $variant = null;
        if ($model) {
            foreach ($this->variantRepository->findByModel($model->getId()) as $candidate) {
                if ($this->normalizer->equals($candidate->getName(), $variantName ?? '')) {
                    $variant = $candidate;
                    break;
                }
            }
        }

        $engineRows = [];
        foreach ($block['engines'] as $engine) {
            $engineExists = false;
            $engineId = null;
            if ($variant) {
                foreach ($this->variantRepository ? $this->engineRepository->findByVariant($variant->getId()) : [] as $candidate) {
                    if ($this->normalizer->equals($candidate->getLabel(), $engine['label']) && $candidate->getPowerCv() === $engine['powerCv']) {
                        $engineExists = true;
                        $engineId = $candidate->getId();
                        break;
                    }
                }
            }

            $engineRows[] = $engine + ['engineExists' => $engineExists, 'engineId' => $engineId];
        }

        $guessedBrandName = $brand ? $brand->getName() : $this->guessBrandName($prefix);

        return [
            'headerPrefix' => $prefix,
            'brand' => $brand ? ['found' => true, 'id' => $brand->getId(), 'name' => $brand->getName()] : ['found' => false, 'id' => null, 'name' => $guessedBrandName],
            'model' => $model ? ['found' => true, 'id' => $model->getId(), 'name' => $model->getName()] : ['found' => false, 'id' => null, 'name' => $modelName],
            'variant' => $variant ? ['found' => true, 'id' => $variant->getId(), 'name' => $variant->getName()] : ['found' => false, 'id' => null, 'name' => $variantName],
            'periodBegin' => $block['periodBegin'],
            'periodEnd' => $block['periodEnd'],
            'engines' => $engineRows,
        ];
    }

    /** @param Brand[] $brands */
    private function matchBrandPrefix(string $prefix, array $brands): ?Brand
    {
        $normalizedPrefix = $this->normalizer->normalize($prefix);
        $best = null;
        $bestLength = 0;

        foreach ($brands as $brand) {
            foreach (array_filter([$brand->getName(), $brand->getSigle()]) as $candidate) {
                $normalizedCandidate = $this->normalizer->normalize($candidate);
                if ('' !== $normalizedCandidate && str_starts_with($normalizedPrefix, $normalizedCandidate) && mb_strlen($candidate) > $bestLength) {
                    $best = $brand;
                    $bestLength = mb_strlen($candidate);
                }
            }
        }

        return $best;
    }


    /** Meilleure estimation du nom de marque quand aucune correspondance n'a été trouvée en base — modifiable par l'admin avant création. */
    private function guessBrandName(string $prefix): string
    {
        $firstWord = explode(' ', trim($prefix), 2)[0] ?? '';

        return $firstWord;
    }

    /** @param VehicleModel[] $models */
    private function matchModelPrefix(string $remainder, array $models): ?array
    {
        $normalizedRemainder = $this->normalizer->normalize($remainder);
        $best = null;
        $bestLength = 0;

        foreach ($models as $model) {
            $normalizedName = $this->normalizer->normalize($model->getName());
            if ('' !== $normalizedName && str_starts_with($normalizedRemainder, $normalizedName) && mb_strlen($model->getName()) > $bestLength) {
                $best = $model;
                $bestLength = mb_strlen($model->getName());
            }
        }

        if (null === $best) {
            return null;
        }

        return ['model' => $best, 'remainder' => trim(mb_substr($remainder, $bestLength))];
    }
}