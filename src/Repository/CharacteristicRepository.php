<?php

namespace App\Repository;

use App\Entity\Characteristic;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CharacteristicRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Characteristic::class);
    }

    /** @return Characteristic[] */
    public function searchByName(string $term, int $limit = 15): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.name LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('c.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOneByExactMatch(string $name, ?string $unit, string $dataType): ?Characteristic
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.name = :name')->setParameter('name', $name)
            ->andWhere('c.dataType = :dataType')->setParameter('dataType', $dataType);

        if (null === $unit) {
            $qb->andWhere('c.unit IS NULL');
        } else {
            $qb->andWhere('c.unit = :unit')->setParameter('unit', $unit);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}