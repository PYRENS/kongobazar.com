<?php

namespace App\Repository;

use App\Entity\PromoStripSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PromoStripSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PromoStripSetting::class);
    }

    public function getSingleton(): PromoStripSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new PromoStripSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }
        return $setting;
    }
}
