<?php

namespace App\Repository;

use App\Entity\Campaign;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CampaignRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Campaign::class);
    }

    /** @return Campaign[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.startAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * La campagne actuellement "en direct" (active ET dans sa période), s'il y en a une.
     * En cas de chevauchement improbable de 2 campagnes, la plus récemment démarrée gagne.
     */
    public function findCurrentlyLive(): ?Campaign
    {
        $now = new \DateTimeImmutable();
        return $this->createQueryBuilder('c')
            ->andWhere('c.active = true')
            ->andWhere('c.startAt <= :now')
            ->andWhere('c.endAt > :now')
            ->setParameter('now', $now)
            ->orderBy('c.startAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
