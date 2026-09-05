<?php

namespace App\Repository;

use App\Entity\IndividualSectionCategory;
use App\Entity\IndividualSectionPriorityProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class IndividualSectionPriorityProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IndividualSectionPriorityProduct::class);
    }

    /** @return IndividualSectionPriorityProduct[] */
    public function findByCategoryOrdered(IndividualSectionCategory $sectionCategory): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.sectionCategory = :sc')
            ->setParameter('sc', $sectionCategory)
            ->orderBy('p.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNextPosition(IndividualSectionCategory $sectionCategory): int
    {
        $max = $this->createQueryBuilder('p')
            ->select('MAX(p.position)')
            ->andWhere('p.sectionCategory = :sc')
            ->setParameter('sc', $sectionCategory)
            ->getQuery()
            ->getSingleScalarResult();
        return ($max ?? -1) + 1;
    }
}
