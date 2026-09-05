<?php

namespace App\Repository;

use App\Entity\TopVendorSetting;
use App\Entity\TopVendorTargetedSeller;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TopVendorTargetedSellerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TopVendorTargetedSeller::class);
    }

    /** @return TopVendorTargetedSeller[] */
    public function findBySettingOrdered(TopVendorSetting $setting): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.setting = :setting')
            ->setParameter('setting', $setting)
            ->orderBy('t.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNextPosition(TopVendorSetting $setting): int
    {
        $max = $this->createQueryBuilder('t')
            ->select('MAX(t.position)')
            ->andWhere('t.setting = :setting')
            ->setParameter('setting', $setting)
            ->getQuery()
            ->getSingleScalarResult();

        return ($max ?? -1) + 1;
    }
}
