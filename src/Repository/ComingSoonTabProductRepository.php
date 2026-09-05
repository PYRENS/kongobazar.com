<?php

namespace App\Repository;

use App\Entity\ComingSoonTab;
use App\Entity\ComingSoonTabProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ComingSoonTabProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComingSoonTabProduct::class);
    }

    /** @return ComingSoonTabProduct[] */
    public function findByTabOrdered(ComingSoonTab $tab): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.tab = :tab')
            ->setParameter('tab', $tab)
            ->orderBy('p.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNextPosition(ComingSoonTab $tab): int
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
