<?php

namespace App\Repository;

use App\Entity\PartEngineCompatibility;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PartEngineCompatibilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PartEngineCompatibility::class);
    }

    /** Nombre de pièces (produits actifs) compatibles avec cette motorisation. */
    public function countActiveProductsByEngine(int $engineId): int
    {
        return (int) $this->createQueryBuilder('pec')
            ->select('COUNT(DISTINCT p.id)')
            ->join('pec.partListingDetails', 'pld')
            ->join('pld.product', 'p')
            ->andWhere('pec.vehicleEngine = :engineId')
            ->andWhere('p.status = :status')
            ->setParameter('engineId', $engineId)
            ->setParameter('status', 'active')
            ->getQuery()
            ->getSingleScalarResult();
    }
}