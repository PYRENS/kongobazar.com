<?php

namespace App\Repository;

use App\Entity\BestSellersSectionSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BestSellersSectionSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BestSellersSectionSetting::class);
    }

    public function getSingleton(): BestSellersSectionSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new BestSellersSectionSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }
        return $setting;
    }
}
