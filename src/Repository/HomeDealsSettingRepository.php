<?php

namespace App\Repository;

use App\Entity\HomeDealsSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HomeDealsSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HomeDealsSetting::class);
    }

    public function getSingleton(): HomeDealsSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new HomeDealsSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }

        return $setting;
    }
}
