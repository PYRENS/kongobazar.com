<?php

namespace App\Repository;

use App\Entity\SocialFloatSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class SocialFloatSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SocialFloatSetting::class);
    }

    public function getSingleton(): SocialFloatSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new SocialFloatSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }

        return $setting;
    }
}