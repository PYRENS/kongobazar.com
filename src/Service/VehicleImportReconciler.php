<?php

namespace App\Service;

use App\Entity\Brand;
use App\Repository\BrandRepository;
use App\Repository\FuelTypeRepository;
use App\Repository\VehicleEngineRepository;
use App\Repository\VehicleModelRepository;
use App\Repository\VehicleVariantRepository;

class VehicleImportReconciler
{
    public function __construct(
        private readonly VehicleTextNormalizer $normalizer,
        private readonly BrandRepository $brandRepository,
        private readonly VehicleModelRepository $modelRepository,
        private readonly VehicleVariantRepository $variantRepository,
        private readonly FuelTypeRepository $fuelTypeRepository,
        private readonly VehicleEngineRepository $engineRepository,
    ) {
    }

    public function reconcile(array $parsed, string $type): array
    {
        if (isset($parsed['error'])) {
            return $parsed;
        }

        $brand = $this->findBrandByNameOrSigle($this->brandRepository->findAll(), $parsed['brandName']);

        $model = null;
        if ($brand) {
            $model = $this->findByName($this->modelRepository->findByBrand($brand->getId()), $parsed['modelName']);
        }

        $variant = null;
        if ($model && 'auto' === $type) {
            $variant = $this->findByName($this->variantRepository->findByModel($model->getId()), $parsed['variantName'] ?? '', true);
        }

        $rows = [];
        foreach ($parsed['rows'] as $row) {
            $fuel = $row['fuelName'] ? $this->findByName($this->fuelTypeRepository->findAll(), $row['fuelName']) : null;

            $engineExists = false;
            $engineId = null;
            if ($model) {
                $candidates = [];
                if ('auto' === $type && $variant) {
                    $candidates = $this->engineRepository->findByVariant($variant->getId());
                } elseif ('moto' === $type) {
                    $candidates = $this->engineRepository->findByModel($model->getId());
                }

                foreach ($candidates as $candidate) {
                    if ($this->normalizer->equals($candidate->getLabel(), $row['label'])
                        && $candidate->getPowerCv() === $row['powerCv']
                    ) {
                        $engineExists = true;
                        $engineId = $candidate->getId();
                        break;
                    }
                }
            }

            $rows[] = [
                'fuel' => [
                    'inputName' => $row['fuelName'],
                    'found' => (bool) $fuel,
                    'id' => $fuel?->getId(),
                    'name' => $fuel?->getName(),
                ],
                'label' => $row['label'],
                'powerCv' => $row['powerCv'],
                'powerKw' => $row['powerKw'],
                'displacementCc' => $row['displacementCc'],
                'periodBegin' => $row['periodBegin'],
                'periodEnd' => $row['periodEnd'],
                'engineExists' => $engineExists,
                'engineId' => $engineId,
            ];
        }

        return [
            'brand' => [
                'inputName' => $parsed['brandName'],
                'found' => (bool) $brand,
                'id' => $brand?->getId(),
                'name' => $brand?->getName(),
            ],
            'model' => [
                'inputName' => $parsed['modelName'],
                'found' => (bool) $model,
                'id' => $model?->getId(),
                'name' => $model?->getName(),
            ],
            'variant' => 'auto' === $type ? [
                'inputName' => $parsed['variantName'],
                'found' => (bool) $variant,
                'id' => $variant?->getId(),
                'name' => $variant?->getName(),
            ] : null,
            'modelPeriodBegin' => $parsed['modelPeriodBegin'],
            'modelPeriodEnd' => $parsed['modelPeriodEnd'],
            'rows' => $rows,
            'unrecognizedLines' => $parsed['unrecognizedLines'],
        ];
    }

    private function findBrandByNameOrSigle(array $brands, ?string $name): ?Brand
    {
        if (null === $name || '' === trim($name)) {
            return null;
        }

        foreach ($brands as $brand) {
            if ($this->normalizer->equals($brand->getName(), $name) || $this->normalizer->equals($brand->getSigle(), $name)) {
                return $brand;
            }
        }

        return null;
    }

    /** @param object[] $candidates */
    private function findByName(array $candidates, ?string $name, bool $allowEmptyMatch = false): ?object
    {
        if (null === $name || '' === trim($name)) {
            if ($allowEmptyMatch) {
                foreach ($candidates as $candidate) {
                    if (null === $candidate->getName()) {
                        return $candidate;
                    }
                }
            }
            return null;
        }

        foreach ($candidates as $candidate) {
            if ($this->normalizer->equals($candidate->getName(), $name)) {
                return $candidate;
            }
        }

        return null;
    }
}