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
