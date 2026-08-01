<?php
// src/Repository/SellerProfileRepository.php
namespace App\Repository;

use App\Entity\AdministrativeUnit;
use App\Entity\SellerProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SellerProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SellerProfile::class);
    }

    public function findTopVendors(int $limit = 4): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('s.createdAt', 'DESC') // à affiner plus tard avec un vrai critère de "ventes totales"
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchDirectory(?string $term, ?string $type, ?AdministrativeUnit $location): array
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->setParameter('status', 'active');

        if ($term) {
            $qb->andWhere('s.displayName LIKE :term')
            ->setParameter('term', '%' . $term . '%');
        }

        if ($type) {
            $qb->andWhere('s INSTANCE OF ' . match ($type) {
                'store' => 'App\Entity\StoreProfile',
                'pro' => 'App\Entity\ProProfile',
                'relay' => 'App\Entity\RelayProfile',
                'individual' => 'App\Entity\IndividualProfile',
                default => 'App\Entity\SellerProfile',
            });
        }

        if ($location) {
            if ($type === 'relay') {
                // Un point relais est cherché par sa localisation physique (celle de son User)
                $qb->join('s.user', 'u')
                ->andWhere('u.administrativeUnit = :location')
                ->setParameter('location', $location);
            } else {
                // Un Pro/Boutique est cherché par sa zone de livraison déclarée
                $qb->join('s.deliveryZones', 'z')
                ->andWhere('z = :location')
                ->setParameter('location', $location);
            }
        }

        return $qb->getQuery()->getResult();
    }


    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByType(string $type): int
    {
        $class = match ($type) {
            'store' => \App\Entity\StoreProfile::class,
            'pro' => \App\Entity\ProProfile::class,
            'relay' => \App\Entity\RelayProfile::class,
            default => throw new \InvalidArgumentException("Type inconnu : {$type}"),
        };

        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s INSTANCE OF :class')
            ->setParameter('class', $class)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOneBySlug(string $slug): ?\App\Entity\SellerProfile
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function countByTypeBreakdown(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        return $conn->fetchAllAssociative(
            "SELECT type, COUNT(*) as total FROM seller_profile GROUP BY type"
        );
    }

    public function findOneByUser(\App\Entity\User $user): ?\App\Entity\SellerProfile
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getTypeLabel(\App\Entity\SellerProfile $profile): string
    {
        return match (true) {
            $profile instanceof \App\Entity\StoreProfile => 'Boutique',
            $profile instanceof \App\Entity\ProProfile => 'Vendeur Pro',
            $profile instanceof \App\Entity\RelayProfile => 'Point Relais',
            $profile instanceof \App\Entity\IndividualProfile => 'Particulier',
            default => 'Particulier',
        };
    }

}