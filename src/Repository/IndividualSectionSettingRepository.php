<?php

namespace App\Repository;

use App\Entity\IndividualSectionSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class IndividualSectionSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IndividualSectionSetting::class);
    }

    public function getSingleton(): IndividualSectionSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new IndividualSectionSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }
        return $setting;
    }
}
