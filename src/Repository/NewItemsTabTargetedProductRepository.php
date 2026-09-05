<?php

namespace App\Repository;

use App\Entity\NewItemsTab;
use App\Entity\NewItemsTabTargetedProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NewItemsTabTargetedProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewItemsTabTargetedProduct::class);
    }

    /** @return NewItemsTabTargetedProduct[] */
    public function findByTabOrdered(NewItemsTab $tab): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.tab = :tab')
            ->setParameter('tab', $tab)
            ->orderBy('p.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNextPosition(NewItemsTab $tab): int
    {
        $max = $this->createQueryBuilder('p')
            ->select('MAX(p.position)')
            ->andWhere('p.tab = :tab')
            ->setParameter('tab', $tab)
            ->getQuery()
            ->getSingleScalarResult();

        return ($max ?? -1) + 1;
    }
}
