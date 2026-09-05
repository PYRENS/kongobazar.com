<?php

namespace App\Repository;

use App\Entity\SponsorSectionSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SponsorSectionSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SponsorSectionSetting::class);
    }

    public function getSingleton(): SponsorSectionSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new SponsorSectionSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }
        return $setting;
    }
}
