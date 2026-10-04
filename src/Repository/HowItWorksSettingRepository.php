<?php

namespace App\Repository;

use App\Entity\HowItWorksSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HowItWorksSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HowItWorksSetting::class);
    }

    public function getSingleton(): HowItWorksSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new HowItWorksSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }

        return $setting;
    }
}