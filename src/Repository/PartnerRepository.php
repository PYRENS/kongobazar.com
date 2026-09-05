<?php

namespace App\Repository;

use App\Entity\Partner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PartnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Partner::class);
    }

    /** @return Partner[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Partner[] */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.active = true')
            ->orderBy('p.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNextPosition(): int
    {
        $max = $this->createQueryBuilder('p')->select('MAX(p.position)')->getQuery()->getSingleScalarResult();
        return ($max ?? -1) + 1;
    }
}
