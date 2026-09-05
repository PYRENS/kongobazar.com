<?php

namespace App\Repository;

use App\Entity\TopVendorSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TopVendorSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TopVendorSetting::class);
    }

    public function getSingleton(): TopVendorSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new TopVendorSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }

        return $setting;
    }
}
