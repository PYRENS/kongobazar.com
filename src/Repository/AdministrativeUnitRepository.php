<?php

namespace App\Repository;

use App\Entity\AdministrativeUnit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrativeUnit>
 */
class AdministrativeUnitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrativeUnit::class);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByActive(bool $active): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.active = :active')
            ->setParameter('active', $active)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findRootUnits(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.parent IS NULL')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveRootUnits(): array
    {
        $kinshasa = $this->createQueryBuilder('a')
            ->andWhere('a.parent IS NULL')
            ->andWhere('a.active = true')
            ->andWhere('a.name = :capital')
            ->setParameter('capital', 'Kinshasa')
            ->getQuery()
            ->getResult();

        $others = $this->createQueryBuilder('a')
            ->andWhere('a.parent IS NULL')
            ->andWhere('a.active = true')
            ->andWhere('a.name != :capital')
            ->setParameter('capital', 'Kinshasa')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();

        return array_merge($kinshasa, $others);
    }

    public function findActiveChildren(int $parentId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.parent = :parentId')
            ->andWhere('a.active = true')
            ->setParameter('parentId', $parentId)
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findChildrenOf(?int $parentId): array
    {
        $qb = $this->createQueryBuilder('a')->orderBy('a.name', 'ASC');

        if ($parentId) {
            $qb->andWhere('a.parent = :parentId')->setParameter('parentId', $parentId);
        } else {
            $qb->andWhere('a.parent IS NULL');
        }

        $results = $qb->getQuery()->getResult();

        // Kinshasa toujours en tête de liste au niveau racine (province la plus utilisée).
        if (!$parentId) {
            usort($results, fn ($a, $b) => (str_starts_with(mb_strtolower($a->getName()), 'kinshasa') ? -1 : 0)
                <=> (str_starts_with(mb_strtolower($b->getName()), 'kinshasa') ? -1 : 0));
        }

        return $results;
    }

    public function countChildrenOf(int $unitId): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.parent = :unitId')
            ->setParameter('unitId', $unitId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAllDescendants(\App\Entity\AdministrativeUnit $unit): int
    {
        $children = $this->findBy(['parent' => $unit]);
        $count = count($children);
        foreach ($children as $child) {
            $count += $this->countAllDescendants($child);
        }
        return $count;
    }

    /** Un lieu choisi (ex. une Province) + tous les lieux qu'il contient (villes, communes...),
     *  pour qu'un vendeur livrant à une commune précise ressorte quand on filtre sur sa province. */
    public function getDescendantIds(int $unitId): array
    {
        $ids = [$unitId];
        $children = $this->findBy(['parent' => $unitId]);
        foreach ($children as $child) {
            $ids = array_merge($ids, $this->getDescendantIds($child->getId()));
        }
        return $ids;
    }

    /** Un ensemble de lieux + tout ce qu'ils contiennent chacun (utile quand plusieurs lieux
     *  correspondent au terme tapé, contrairement à getDescendantIds qui n'en prend qu'un). */
    /** Distance réelle en kilomètres entre 2 points GPS (formule de Haversine). */
    public static function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    /** Le lieu le plus proche (toutes niveaux confondus) d'un point GPS donné — utilisé pour un
     *  visiteur non identifié, dont on ne connaît que la position réelle du navigateur. */
    public function findNearestUnit(float $lat, float $lon): ?AdministrativeUnit
    {
        $candidates = $this->createQueryBuilder('a')
            ->andWhere('a.latitude IS NOT NULL')
            ->andWhere('a.longitude IS NOT NULL')
            ->andWhere('a.level = 4') // on cherche au niveau le plus précis (Quartier)
            ->getQuery()->getResult();

        $nearest = null;
        $nearestDistance = null;
        foreach ($candidates as $unit) {
            $distance = self::haversineKm($lat, $lon, $unit->getLatitude(), $unit->getLongitude());
            if (null === $nearestDistance || $distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearest = $unit;
            }
        }

        return $nearest;
    }

    /** Tous les lieux du même niveau que $center, situés dans un rayon donné (en kilomètres). */
    public function findWithinRadius(AdministrativeUnit $center, float $radiusKm): array
    {
        if (null === $center->getLatitude() || null === $center->getLongitude()) {
            return [];
        }

        $candidates = $this->createQueryBuilder('a')
            ->andWhere('a.level = :level')->setParameter('level', $center->getLevel())
            ->andWhere('a.latitude IS NOT NULL')
            ->andWhere('a.longitude IS NOT NULL')
            ->getQuery()->getResult();

        $nearbyIds = [];
        foreach ($candidates as $unit) {
            if (self::haversineKm($center->getLatitude(), $center->getLongitude(), $unit->getLatitude(), $unit->getLongitude()) <= $radiusKm) {
                $nearbyIds[] = $unit->getId();
            }
        }

        return $nearbyIds;
    }

    public function getDescendantIdsForMany(array $unitIds): array
    {
        $all = [];
        foreach ($unitIds as $id) {
            $all = array_merge($all, $this->getDescendantIds($id));
        }
        return array_unique($all);
    }

    public function searchByName(string $term): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.name LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('a.level', 'ASC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return AdministrativeUnit[] Returns an array of AdministrativeUnit objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?AdministrativeUnit
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
