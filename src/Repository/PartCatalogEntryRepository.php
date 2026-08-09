<?php

namespace App\Repository;

use App\Entity\PartCatalogEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PartCatalogEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PartCatalogEntry::class);
    }

    public function findByEanOrManufacturerRef(?string $ean, ?string $manufacturerRef): ?PartCatalogEntry
    {
        if ($ean) {
            $found = $this->findOneBy(['ean' => $ean]);
            if ($found) {
                return $found;
            }
        }
        if ($manufacturerRef) {
            return $this->findOneBy(['manufacturerRef' => $manufacturerRef]);
        }
        return null;
    }

    /** @return PartCatalogEntry[] */
    public function findFiltered(?string $status, ?string $term): array
    {
        $qb = $this->createQueryBuilder('e')->orderBy('e.createdAt', 'DESC');

        if ($status) {
            $qb->andWhere('e.status = :status')->setParameter('status', $status);
        }
        if ($term) {
            $qb->andWhere('e.name LIKE :term OR e.ean LIKE :term OR e.manufacturerRef LIKE :term')
                ->setParameter('term', '%' . $term . '%');
        }

        return $qb->getQuery()->getResult();
    }
}