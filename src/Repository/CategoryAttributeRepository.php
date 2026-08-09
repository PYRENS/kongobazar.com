<?php

namespace App\Repository;

use App\Entity\CategoryAttribute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CategoryAttributeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategoryAttribute::class);
    }

    public function findMaxPosition(int $categoryId): int
    {
        $result = $this->createQueryBuilder('a')
            ->select('MAX(a.position)')
            ->andWhere('a.category = :categoryId')
            ->setParameter('categoryId', $categoryId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }

    /** @return CategoryAttribute[] */
    public function findByCategory(int $categoryId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.category = :categoryId')
            ->setParameter('categoryId', $categoryId)
            ->orderBy('a.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}