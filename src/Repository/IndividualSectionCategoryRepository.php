<?php

namespace App\Repository;

use App\Entity\IndividualSectionCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class IndividualSectionCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IndividualSectionCategory::class);
    }

    /** @return IndividualSectionCategory[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNextPosition(): int
    {
        $max = $this->createQueryBuilder('c')
            ->select('MAX(c.position)')
            ->getQuery()
            ->getSingleScalarResult();
        return ($max ?? -1) + 1;
    }
}
