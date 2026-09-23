<?php

namespace App\Repository;

use App\Entity\LoginBackgroundSectionSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LoginBackgroundSectionSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginBackgroundSectionSetting::class);
    }

    public function getSingleton(): LoginBackgroundSectionSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new LoginBackgroundSectionSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }
        return $setting;
    }
}