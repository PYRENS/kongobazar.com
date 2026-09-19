<?php

namespace App\Repository;

use App\Entity\SidebarFillerBanner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SidebarFillerBannerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SidebarFillerBanner::class);
    }

    /** @return SidebarFillerBanner[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('b')
            ->orderBy('b.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return SidebarFillerBanner[] — toutes les bannières actives, dans un ordre aléatoire. */
    public function findActiveRandomOrder(): array
    {
        $banners = $this->createQueryBuilder('b')
            ->andWhere('b.active = true')
            ->getQuery()
            ->getResult();

        shuffle($banners);

        return $banners;
    }
}
