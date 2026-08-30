<?php

namespace App\Repository;

use App\Entity\HomeCategoryBlockSectionSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HomeCategoryBlockSectionSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HomeCategoryBlockSectionSetting::class);
    }

    public function getSingleton(): HomeCategoryBlockSectionSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new HomeCategoryBlockSectionSetting();
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }

        return $setting;
    }
}
