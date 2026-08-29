<?php

namespace App\Repository;

use App\Entity\SeoOverride;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SeoOverrideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SeoOverride::class);
    }

    public function findOverride(string $entityType, ?int $entityId, ?string $pageKey): ?SeoOverride
    {
        if ($pageKey) {
            return $this->findOneBy(['entityType' => $entityType, 'pageKey' => $pageKey]);
        }

        return $this->findOneBy(['entityType' => $entityType, 'entityId' => $entityId]);
    }

    /** @return SeoOverride[] */
    public function findFiltered(?string $entityType, ?string $term): array
    {
        $qb = $this->createQueryBuilder('s')->orderBy('s.entityType', 'ASC')->addOrderBy('s.adminLabel', 'ASC');

        if ($entityType) {
            $qb->andWhere('s.entityType = :entityType')->setParameter('entityType', $entityType);
        }
        if ($term) {
            $qb->andWhere('s.adminLabel LIKE :term OR s.metaTitle LIKE :term OR s.pageKey LIKE :term')
                ->setParameter('term', '%' . $term . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
