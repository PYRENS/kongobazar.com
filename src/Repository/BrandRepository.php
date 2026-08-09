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

    /** @param string $type 'auto' ou 'moto' */
    public function findByType(string $type): array
    {
        $brands = $this->findBy([], ['name' => 'ASC']);

        return array_values(array_filter($brands, fn (\App\Entity\Brand $b) => $b->hasType($type)));
    }

    /** @return Brand[] */
    public function searchByName(string $term, int $limit = 15): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.name LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('b.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** Marques typées Auto et/ou Moto uniquement (exclut les marques génériques). */
    public function findVehicleBrands(): array
    {
        $brands = $this->findBy([], ['name' => 'ASC']);

        return array_values(array_filter($brands, fn (\App\Entity\Brand $b) => $b->hasType('auto') || $b->hasType('moto')));
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
