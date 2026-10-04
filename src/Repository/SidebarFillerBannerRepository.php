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

    /** @return SidebarFillerBanner[] — pour une catégorie donnée : priorité aux bannières
     *  dédiées à cette catégorie ou l'un de ses ancêtres, puis toutes les autres en repli
     *  (généralistes ou dédiées ailleurs) si la première liste ne suffit pas à combler. */
    public function findForCategory(\App\Entity\Category $category): array
    {
        $chainIds = [$category->getId()];
        foreach ($category->getAncestors() as $ancestor) {
            $chainIds[] = $ancestor->getId();
        }

        $all = $this->createQueryBuilder('b')
            ->andWhere('b.active = true')
            ->getQuery()
            ->getResult();

        $dedicated = [];
        $others = [];
        foreach ($all as $banner) {
            $bannerCategoryIds = array_map(fn ($c) => $c->getId(), $banner->getCategories()->toArray());
            if ($bannerCategoryIds && array_intersect($bannerCategoryIds, $chainIds)) {
                $dedicated[] = $banner;
            } else {
                $others[] = $banner;
            }
        }

        shuffle($dedicated);
        shuffle($others);

        return array_merge($dedicated, $others);
    }
}
