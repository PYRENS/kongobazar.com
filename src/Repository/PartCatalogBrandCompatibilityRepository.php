<?php

namespace App\Repository;

use App\Entity\PartCatalogBrandCompatibility;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PartCatalogBrandCompatibilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PartCatalogBrandCompatibility::class);
    }
}