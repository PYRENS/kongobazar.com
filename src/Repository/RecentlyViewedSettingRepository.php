<?php

namespace App\Repository;

use App\Entity\RecentlyViewedSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RecentlyViewedSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecentlyViewedSetting::class);
    }

    public function getSingleton(): RecentlyViewedSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new RecentlyViewedSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }
        return $setting;
    }
}