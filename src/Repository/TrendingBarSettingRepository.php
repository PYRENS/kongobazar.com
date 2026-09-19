<?php

namespace App\Repository;

use App\Entity\TrendingBarSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TrendingBarSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrendingBarSetting::class);
    }

    public function getSingleton(): TrendingBarSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new TrendingBarSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }
        return $setting;
    }
}
