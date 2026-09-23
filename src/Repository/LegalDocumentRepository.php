<?php

namespace App\Repository;

use App\Entity\LegalDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LegalDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LegalDocument::class);
    }

    /** @return LegalDocument[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('d')
            ->orderBy('d.targetSpace', 'ASC')
            ->addOrderBy('d.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCode(string $code): ?LegalDocument
    {
        return $this->findOneBy(['code' => $code]);
    }
}