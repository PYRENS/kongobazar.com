<?php

namespace App\Repository;

use App\Entity\ComingSoonTab;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ComingSoonTabRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComingSoonTab::class);
    }

    /** @return ComingSoonTab[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNextPosition(): int
    {
        $max = $this->createQueryBuilder('t')
            ->select('MAX(t.position)')
            ->getQuery()
            ->getSingleScalarResult();
        return ($max ?? -1) + 1;
    }
}
