<?php

namespace App\Repository;

use App\Entity\SiteIdentitySetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SiteIdentitySettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteIdentitySetting::class);
    }

    public function getSingleton(): SiteIdentitySetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new SiteIdentitySetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }

        return $setting;
    }
}
