<?php

namespace App\Repository;

use App\Entity\PromoStripBanner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PromoStripBannerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PromoStripBanner::class);
    }

    /** @return PromoStripBanner[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('b')
            ->orderBy('b.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return PromoStripBanner[] */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.active = true')
            ->orderBy('b.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNextPosition(): int
    {
        $max = $this->createQueryBuilder('b')
            ->select('MAX(b.position)')
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $max) + 1;
    }
}
