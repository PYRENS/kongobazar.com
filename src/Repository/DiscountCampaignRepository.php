<?php

namespace App\Repository;

use App\Entity\DiscountCampaign;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DiscountCampaign>
 */
class DiscountCampaignRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscountCampaign::class);
    }

    /** @return DiscountCampaign[] */
    public function findFiltered(?string $term, ?array $categoryIds = null, ?array $unitIds = null, ?int $sellerId = null, string $sort = 'createdAt', string $dir = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('d')
            ->join('d.product', 'p');

        if ($term) {
            $conditions = $qb->expr()->orX(
                $qb->expr()->like('p.title', ':term'),
            );
            $qb->setParameter('term', '%' . $term . '%');

            if (preg_match('/(\d+)/', $term, $m)) {
                $kbzId = (int) ltrim($m[1], '0');
                if ($kbzId > 0) {
                    $conditions->add($qb->expr()->eq('p.id', ':kbzId'));
                    $qb->setParameter('kbzId', $kbzId);
                }
            }

            $qb->andWhere($conditions);
        }

        if ($categoryIds) {
            $qb->andWhere('p.category IN (:categoryIds)')->setParameter('categoryIds', $categoryIds);
        }

        if ($unitIds || $sellerId) {
            $qb->join('p.sellerProfile', 'sp');

            if ($unitIds) {
                $qb->join('sp.deliveryZones', 'dz')
                    ->andWhere('dz.id IN (:unitIds)')->setParameter('unitIds', $unitIds);
            }
            if ($sellerId) {
                $qb->andWhere('sp.id = :sellerId')->setParameter('sellerId', $sellerId);
            }
        }

        $sortMap = [
            'product' => 'p.title',
            'basePrice' => 'p.basePrice',
            'discountedPrice' => 'd.discountedPrice',
            'startAt' => 'd.startAt',
            'endAt' => 'd.endAt',
            'status' => 'd.status',
            'createdAt' => 'd.createdAt',
        ];

        if (isset($sortMap[$sort])) {
            $qb->orderBy($sortMap[$sort], $dir);
        } else {
            $qb->orderBy('d.createdAt', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    //    /**
    //     * @return DiscountCampaign[] Returns an array of DiscountCampaign objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?DiscountCampaign
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function countSoldDuringCampaign(\App\Entity\DiscountCampaign $campaign): int
    {
        $result = $this->getEntityManager()->createQueryBuilder()
            ->select('COALESCE(SUM(oi.quantity), 0)')
            ->from(\App\Entity\OrderItem::class, 'oi')
            ->join('oi.order', 'o')
            ->andWhere('oi.product = :product')
            ->andWhere('o.createdAt >= :start')
            ->andWhere('o.createdAt <= :end')
            ->andWhere('o.status NOT IN (:excludedStatuses)')
            ->setParameter('product', $campaign->getProduct())
            ->setParameter('start', $campaign->getStartAt())
            ->setParameter('end', $campaign->getEndAt())
            ->setParameter('excludedStatuses', ['cancelled', 'refused', 'refunded'])
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    public function findActiveOrScheduledForProduct(\App\Entity\Product $product): ?\App\Entity\DiscountCampaign
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.product = :product')
            ->andWhere('d.status IN (:statuses)')
            ->setParameter('product', $product)
            ->setParameter('statuses', ['scheduled', 'active'])
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllForAdmin(): array
    {
        return $this->createQueryBuilder('d')
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Passées de "scheduled" à "active" : la date de début est atteinte, celle de fin pas encore. */
    public function findScheduledToActivate(): array
    {
        $now = new \DateTimeImmutable();
        return $this->createQueryBuilder('d')
            ->andWhere('d.status = :status')
            ->andWhere('d.startAt <= :now')
            ->andWhere('d.endAt > :now')
            ->setParameter('status', 'scheduled')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    /** Passées de "active" à "expired" : la date de fin est dépassée. */
    public function findActiveToExpire(): array
    {
        $now = new \DateTimeImmutable();
        return $this->createQueryBuilder('d')
            ->andWhere('d.status = :status')
            ->andWhere('d.endAt <= :now')
            ->setParameter('status', 'active')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }
}
