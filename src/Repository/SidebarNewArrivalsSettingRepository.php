<?php

namespace App\Repository;

use App\Entity\SidebarNewArrivalsSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SidebarNewArrivalsSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SidebarNewArrivalsSetting::class);
    }

    public function getSingleton(): SidebarNewArrivalsSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new SidebarNewArrivalsSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }
        return $setting;
    }
}
