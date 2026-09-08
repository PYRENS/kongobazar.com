<?php

namespace App\Repository;

use App\Entity\PreorderSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PreorderSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PreorderSetting::class);
    }

    public function getSingleton(): PreorderSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new PreorderSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }
        return $setting;
    }
}
