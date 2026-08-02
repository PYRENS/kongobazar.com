<?php

namespace App\Repository;

use App\Entity\Brand;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Brand>
 */
class BrandRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Brand::class);
    }

    public function findFeaturedHomepage(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.featuredHomepage = true')
            ->orderBy('b.featuredHomepagePosition', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Marques typées Auto et/ou Moto uniquement (exclut les marques génériques). */
    public function findVehicleBrands(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.type LIKE :auto OR b.type LIKE :moto')
            ->setParameter('auto', '%"auto"%')
            ->setParameter('moto', '%"moto"%')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Brand[] Returns an array of Brand objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Brand
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
