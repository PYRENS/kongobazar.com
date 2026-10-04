<?php

namespace App\Repository;

use App\Entity\HomeCategoryCarouselItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HomeCategoryCarouselItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HomeCategoryCarouselItem::class);
    }

    /** @return HomeCategoryCarouselItem[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('i')
            ->addSelect('c')
            ->innerJoin('i.category', 'c')
            ->orderBy('i.position', 'ASC')
            ->addOrderBy('i.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNextPosition(): int
    {
        $max = $this->createQueryBuilder('i')
            ->select('MAX(i.position)')
            ->getQuery()
            ->getSingleScalarResult();

        return ($max ?? -1) + 1;
    }
}