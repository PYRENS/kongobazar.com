<?php

namespace App\Repository;

use App\Entity\HeroSideAdsSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HeroSideAdsSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HeroSideAdsSetting::class);
    }

    public function getSingleton(): HeroSideAdsSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new HeroSideAdsSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }

        return $setting;
    }
}