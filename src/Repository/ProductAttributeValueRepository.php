<?php

namespace App\Repository;

use App\Entity\ProductAttributeValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductAttributeValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductAttributeValue::class);
    }

    /** @return ProductAttributeValue[] */
    public function findByProduct(int $productId): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.product = :productId')
            ->setParameter('productId', $productId)
            ->getQuery()
            ->getResult();
    }
}