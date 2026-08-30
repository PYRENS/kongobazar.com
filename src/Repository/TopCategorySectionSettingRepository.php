<?php

namespace App\Repository;

use App\Entity\TopCategorySectionSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TopCategorySectionSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TopCategorySectionSetting::class);
    }

    public function getSingleton(): TopCategorySectionSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new TopCategorySectionSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }

        return $setting;
    }
}
