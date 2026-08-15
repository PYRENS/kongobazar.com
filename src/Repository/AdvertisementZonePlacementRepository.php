<?php

namespace App\Repository;

use App\Entity\AdvertisementZonePlacement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AdvertisementZonePlacementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdvertisementZonePlacement::class);
    }

    public function findOneByAdvertisementAndZone(int $advertisementId, string $zoneKey): ?AdvertisementZonePlacement
    {
        return $this->findOneBy(['advertisement' => $advertisementId, 'zoneKey' => $zoneKey]);
    }
}