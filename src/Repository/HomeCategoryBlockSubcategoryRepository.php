<?php

namespace App\Repository;

use App\Entity\HomeCategoryBlockSetting;
use App\Entity\HomeCategoryBlockSubcategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HomeCategoryBlockSubcategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HomeCategoryBlockSubcategory::class);
    }

    /** @return HomeCategoryBlockSubcategory[] */
    public function findByBlockOrdered(HomeCategoryBlockSetting $block): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.block = :block')
            ->setParameter('block', $block)
            ->orderBy('s.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNextPosition(HomeCategoryBlockSetting $block): int
    {
        $max = $this->createQueryBuilder('s')
            ->select('MAX(s.position)')
            ->andWhere('s.block = :block')
            ->setParameter('block', $block)
            ->getQuery()
            ->getSingleScalarResult();

        return ($max ?? -1) + 1;
    }
}
