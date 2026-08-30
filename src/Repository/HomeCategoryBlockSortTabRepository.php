<?php

namespace App\Repository;

use App\Entity\HomeCategoryBlockSetting;
use App\Entity\HomeCategoryBlockSortTab;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HomeCategoryBlockSortTabRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HomeCategoryBlockSortTab::class);
    }

    /** @return HomeCategoryBlockSortTab[] */
    public function findByBlockOrdered(HomeCategoryBlockSetting $block): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.block = :block')
            ->setParameter('block', $block)
            ->orderBy('t.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByBlockAndSortKey(HomeCategoryBlockSetting $block, string $sortKey): ?HomeCategoryBlockSortTab
    {
        return $this->findOneBy(['block' => $block, 'sortKey' => $sortKey]);
    }
}
