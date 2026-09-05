<?php

namespace App\Repository;

use App\Entity\ComingSoonSectionSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ComingSoonSectionSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComingSoonSectionSetting::class);
    }

    public function getSingleton(): ComingSoonSectionSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new ComingSoonSectionSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }
        return $setting;
    }
}
