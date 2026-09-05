<?php

namespace App\Repository;

use App\Entity\MostViewedSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MostViewedSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MostViewedSetting::class);
    }

    public function getSingleton(): MostViewedSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new MostViewedSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }
        return $setting;
    }
}
