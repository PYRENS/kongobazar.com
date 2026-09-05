<?php

namespace App\Repository;

use App\Entity\NewItemsSectionSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NewItemsSectionSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewItemsSectionSetting::class);
    }

    public function getSingleton(): NewItemsSectionSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new NewItemsSectionSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }

        return $setting;
    }
}
