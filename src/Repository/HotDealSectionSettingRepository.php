<?php

namespace App\Repository;

use App\Entity\HotDealSectionSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HotDealSectionSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HotDealSectionSetting::class);
    }

    public function getSingleton(): HotDealSectionSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new HotDealSectionSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }
        return $setting;
    }
}
