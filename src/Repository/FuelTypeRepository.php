<?php

namespace App\Repository;

use App\Entity\FuelType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FuelType>
 */
class FuelTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FuelType::class);
    }

    /** @return FuelType[] */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.active = true')
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}