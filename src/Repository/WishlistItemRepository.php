<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\WishlistItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WishlistItem>
 */
class WishlistItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WishlistItem::class);
    }

    public function findAllWithVariant(): array
    {
        return $this->createQueryBuilder('w')
            ->getQuery()
            ->getResult();
    }

    /** Nombre d'articles actuellement dans la liste de souhaits de ce client. */
    public function countForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->andWhere('w.user = :user')->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return int[] Tous les IDs de produits que ce client a en liste de souhaits (une seule requête, réutilisable pour toute une page). */
    public function getWishlistedProductIds(User $user): array
    {
        $rows = $this->createQueryBuilder('w')
            ->select('IDENTITY(v.product) AS productId')
            ->join('w.variant', 'v')
            ->andWhere('w.user = :user')->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        return array_map(fn ($row) => (int) $row['productId'], $rows);
    }

    //    /**
    //     * @return WishlistItem[] Returns an array of WishlistItem objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('w.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?WishlistItem
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
