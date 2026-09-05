<?php

namespace App\Repository;

use App\Entity\SponsorBrand;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SponsorBrandRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SponsorBrand::class);
    }

    /** @return SponsorBrand[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return SponsorBrand[] */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.active = true')
            ->orderBy('s.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNextPosition(): int
    {
        $max = $this->createQueryBuilder('s')->select('MAX(s.position)')->getQuery()->getSingleScalarResult();
        return ($max ?? -1) + 1;
    }
}
