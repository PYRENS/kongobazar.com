<?php

namespace App\Repository;

use App\Entity\HomeCategoryCarouselSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HomeCategoryCarouselSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HomeCategoryCarouselSetting::class);
    }

    public function getSingleton(): HomeCategoryCarouselSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new HomeCategoryCarouselSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }

        return $setting;
    }
}