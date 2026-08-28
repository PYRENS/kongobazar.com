<?php

namespace App\Service;

use App\Entity\Brand;
use App\Entity\VehicleModel;
use App\Entity\VehicleVariant;
use App\Repository\VehicleEngineRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Parseur du format "Véhicules associés" collé en texte brut, ex:
 *
 *   HYUNDAI Santa Fe II (CM) (Année de construction 10.2005 - 12.2012)
 *   2.0 CRDi, Année de construction 12.2010 - 12.2012, 1995 ccm, 150 CV
 *   ...
 *
 * Résout marque/modèle par correspondance progressive (mot par mot) contre la base,
 * la variante étant déduite du reste de la ligne d'en-tête.
 */
class PartCatalogVehicleAssociationParser
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly VehicleEngineRepository $engineRepository,
    ) {
    }

    public function parse(string $rawText): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($rawText));
        $groups = [];
        $current = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }

            if ($this->isHeaderLine($line)) {
                if (null !== $current) {
                    $groups[] = $current;
                }
                $current = $this->parseHeaderLine($line);
                continue;
            }

            if (null === $current) {
                continue; // ligne orpheline avant tout en-tête — ignorée
            }

            $engineRow = $this->parseEngineLine($line);
            if (null !== $engineRow) {
                $current['engines'][] = $engineRow;
            } else {
                $current['unrecognizedLines'][] = $line;
            }
        }

        if (null !== $current) {
            $groups[] = $current;
        }

        // Résout marque/modèle/variante + statut "existe déjà" des motorisations
        foreach ($groups as &$group) {
            $this->resolveGroup($group);
        }

        return $groups;
    }

    private function isHeaderLine(string $line): bool
    {
        return str_contains($line, '(Année de construction') && str_ends_with($line, ')');
    }

    private function parseHeaderLine(string $line): array
    {
        preg_match('/^(.*)\(Année de construction\s+(.+)\)\s*$/u', $line, $m);
        $prefix = trim($m[1] ?? $line);
        $periodStr = trim($m[2] ?? '');

        [$periodBegin, $periodEnd] = $this->parsePeriodRange($periodStr);

        return [
            'headerLine' => $line,
            'prefix' => $prefix,
            'periodBegin' => $periodBegin,
            'periodEnd' => $periodEnd,
            'engines' => [],
            'unrecognizedLines' => [],
        ];
    }

    private function parsePeriodRange(string $str): array
    {
        // "10.2005 - 12.2012" ou "02.2018 - ..."
        if (!preg_match('/^(\d{2})\.(\d{4})\s*-\s*(\.{3}|…|(\d{2})\.(\d{4}))\s*$/u', $str, $m)) {
            return [null, null];
        }

        $begin = ['month' => $m[1], 'year' => (int) $m[2]];
        $end = (isset($m[4]) && '' !== $m[4]) ? ['month' => $m[4], 'year' => (int) $m[5]] : null;

        return [$begin, $end];
    }

    private function parseEngineLine(string $line): ?array
    {
        $pattern = '/^(.+?),\s*Année de construction\s+(\d{2}\.\d{4})\s*-\s*(\.{3}|…|\d{2}\.\d{4})\s*,\s*(\d+)\s*ccm\s*,\s*(\d+)\s*(CV|CH)$/ui';
        if (!preg_match($pattern, $line, $m)) {
            return null;
        }

        [$beginMonth, $beginYear] = explode('.', $m[2]);
        $endStr = $m[3];
        $end = ('...' === $endStr || '…' === $endStr) ? null : $endStr;
        $endMonth = $endYear = null;
        if ($end) {
            [$endMonth, $endYear] = explode('.', $end);
        }

        return [
            'raw' => $line,
            'label' => trim($m[1]),
            'periodBegin' => ['month' => $beginMonth, 'year' => (int) $beginYear],
            'periodEnd' => $endMonth ? ['month' => $endMonth, 'year' => (int) $endYear] : null,
            'displacementCc' => (int) $m[4],
            'powerCv' => (int) $m[5], // CH traité comme CV, même valeur numérique
        ];
    }

    private function resolveGroup(array &$group): void
    {
        [$brand, $afterBrand, $brandTried] = $this->resolveBrand($group['prefix']);
        $group['brand'] = ['found' => null !== $brand, 'entity' => $brand, 'id' => $brand?->getId(), 'triedText' => $brandTried, 'inputPrefix' => $group['prefix']];

        $model = null;
        $variantText = $afterBrand;
        $modelTried = null;
        if ($brand) {
            [$model, $variantText, $modelTried] = $this->resolveModel($brand, $afterBrand);
        }
        $group['model'] = ['found' => null !== $model, 'entity' => $model, 'id' => $model?->getId(), 'triedText' => $modelTried, 'inputText' => $afterBrand];

        $variant = null;
        if ($model) {
            $variant = $this->findVariant($model, $variantText, $group['periodBegin']);
        }
        $group['variant'] = ['found' => null !== $variant, 'entity' => $variant, 'id' => $variant?->getId(), 'inputText' => $variantText];

        foreach ($group['engines'] as &$engineRow) {
            $engineRow['exists'] = false;
            $engineRow['hasFuel'] = false;
            if ($variant) {
                foreach ($this->engineRepository->findByVariant($variant->getId()) as $existingEngine) {
                    if (mb_strtolower($existingEngine->getLabel()) === mb_strtolower($engineRow['label'])
                        && $existingEngine->getYearStart() === $engineRow['periodBegin']['year']) {
                        $engineRow['exists'] = true;
                        $engineRow['existingId'] = $existingEngine->getId();
                        $engineRow['hasFuel'] = null !== $existingEngine->getFuelType();
                        break;
                    }
                }
            }
        }
    }

    /** @return array{0: ?Brand, 1: string, 2: string} [marque trouvée ou null, reste du texte, dernier texte essayé] */
    private function resolveBrand(string $prefix): array
    {
        $words = preg_split('/\s+/', trim($prefix));
        $tried = '';

        for ($i = 1; $i <= count($words); $i++) {
            $candidate = trim(implode(' ', array_slice($words, 0, $i)));
            $tried = $candidate;

            $brand = $this->findBrandByName($candidate);
            if (!$brand && 'VW' === mb_strtoupper($candidate)) {
                $brand = $this->findBrandByName('Volkswagen');
            }

            if ($brand) {
                $remainder = trim(implode(' ', array_slice($words, $i)));
                return [$brand, $remainder, $tried];
            }
        }

        return [null, trim($prefix), $tried];
    }

    /** @return array{0: ?VehicleModel, 1: string, 2: ?string} [modèle trouvé ou null, reste = variante, dernier texte essayé] */
    private function resolveModel(Brand $brand, string $remainder): array
    {
        $remainder = trim($remainder);
        if ('' === $remainder) {
            return [null, '', null];
        }

        $words = preg_split('/\s+/', $remainder);

        for ($i = 1; $i <= count($words); $i++) {
            $candidate = trim(implode(' ', array_slice($words, 0, $i)));

            $model = $this->em->getRepository(VehicleModel::class)->createQueryBuilder('m')
                ->andWhere('m.brand = :brand')
                ->andWhere('LOWER(m.name) = :name')
                ->setParameter('brand', $brand)
                ->setParameter('name', mb_strtolower($candidate))
                ->getQuery()
                ->getOneOrNullResult();

            if ($model) {
                $variantText = trim(implode(' ', array_slice($words, $i)));
                return [$model, $variantText, $candidate];
            }
        }

        return [null, $remainder, null];
    }

    private function findVariant(VehicleModel $model, string $name, ?array $periodBegin): ?VehicleVariant
    {
        $qb = $this->em->getRepository(VehicleVariant::class)->createQueryBuilder('v')
            ->andWhere('v.model = :model')
            ->setParameter('model', $model);

        if ('' === trim($name)) {
            $qb->andWhere('v.name IS NULL');
        } else {
            $qb->andWhere('LOWER(v.name) = :name')->setParameter('name', mb_strtolower($name));
        }

        if ($periodBegin) {
            $qb->andWhere('v.yearBegin = :yearBegin')->setParameter('yearBegin', $periodBegin['year']);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }



    private function findBrandByName(string $name): ?Brand
    {
        return $this->em->getRepository(Brand::class)->createQueryBuilder('b')
            ->andWhere('LOWER(b.name) = :name')
            ->setParameter('name', mb_strtolower($name))
            ->getQuery()
            ->getOneOrNullResult();
    }
}