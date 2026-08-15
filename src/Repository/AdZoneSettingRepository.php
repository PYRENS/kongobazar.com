<?php

namespace App\Repository;

use App\Entity\AdZoneSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AdZoneSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdZoneSetting::class);
    }

    public function findOneByZoneKey(string $zoneKey): ?AdZoneSetting
    {
        return $this->findOneBy(['zoneKey' => $zoneKey]);
    }
}