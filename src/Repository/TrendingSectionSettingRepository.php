<?php

namespace App\Repository;

use App\Entity\TrendingSectionSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TrendingSectionSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrendingSectionSetting::class);
    }

    public function getSingleton(): TrendingSectionSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new TrendingSectionSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }

        return $setting;
    }
}
