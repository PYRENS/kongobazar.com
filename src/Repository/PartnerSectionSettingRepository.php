<?php

namespace App\Repository;

use App\Entity\PartnerSectionSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PartnerSectionSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PartnerSectionSetting::class);
    }

    public function getSingleton(): PartnerSectionSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new PartnerSectionSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }
        return $setting;
    }
}
