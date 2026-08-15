<?php

namespace App\Service;

use App\Repository\AdvertisementRepository;

/** Centralise la récupération des pubs actives par zone, pour éviter de répéter la requête dans chaque contrôleur. */
class AdvertisementZoneProvider
{
    public function __construct(private readonly AdvertisementRepository $repository)
    {
    }

    public function one(string $zoneKey, string $targetSpace = 'public'): ?\App\Entity\Advertisement
    {
        $results = $this->repository->findActiveForZone($zoneKey, $targetSpace, 1);
        return $results[0] ?? null;
    }

    /** @return \App\Entity\Advertisement[] */
    public function many(string $zoneKey, int $limit = 5, string $targetSpace = 'public'): array
    {
        return $this->repository->findActiveForZone($zoneKey, $targetSpace, $limit);
    }
}