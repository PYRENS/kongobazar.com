<?php

namespace App\Repository;

use App\Entity\LegalDocument;
use App\Entity\LegalDocumentVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LegalDocumentVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LegalDocumentVersion::class);
    }

    /** @return LegalDocumentVersion[] Les plus récentes en premier. */
    public function findForDocument(LegalDocument $document): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.document = :doc')->setParameter('doc', $document)
            ->orderBy('v.versionNumber', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findLatestPublished(LegalDocument $document): ?LegalDocumentVersion
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.document = :doc')->setParameter('doc', $document)
            ->andWhere('v.status = :status')->setParameter('status', LegalDocumentVersion::STATUS_PUBLISHED)
            ->orderBy('v.versionNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDraft(LegalDocument $document): ?LegalDocumentVersion
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.document = :doc')->setParameter('doc', $document)
            ->andWhere('v.status = :status')->setParameter('status', LegalDocumentVersion::STATUS_DRAFT)
            ->orderBy('v.versionNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getNextVersionNumber(LegalDocument $document): int
    {
        $max = $this->createQueryBuilder('v')
            ->select('MAX(v.versionNumber)')
            ->andWhere('v.document = :doc')->setParameter('doc', $document)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $max) + 1;
    }
}