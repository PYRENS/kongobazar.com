<?php

namespace App\Repository;

use App\Entity\ShareEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ShareEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShareEvent::class);
    }

    /** @return array<int, array{entityType: string, entityId: ?int, pageKey: ?string, adminLabel: ?string, platform: string, total: int}> */
    public function countGroupedByPageAndPlatform(?\DateTimeImmutable $since = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s.entityType, s.entityId, s.pageKey, s.adminLabel, s.platform, COUNT(s.id) AS total')
            ->groupBy('s.entityType, s.entityId, s.pageKey, s.platform')
            ->orderBy('total', 'DESC');

        if ($since) {
            $qb->andWhere('s.createdAt >= :since')->setParameter('since', $since);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByPlatform(?\DateTimeImmutable $since = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s.platform, COUNT(s.id) AS total')
            ->groupBy('s.platform')
            ->orderBy('total', 'DESC');

        if ($since) {
            $qb->andWhere('s.createdAt >= :since')->setParameter('since', $since);
        }

        return $qb->getQuery()->getResult();
    }
}
