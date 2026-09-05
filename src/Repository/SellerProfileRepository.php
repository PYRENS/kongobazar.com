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

    public function searchByName(string $term, int $limit = 15): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.user', 'u')
            ->andWhere('s.displayName LIKE :term OR s.referenceNumber LIKE :term OR u.email LIKE :term')->setParameter('term', '%' . $term . '%')
            ->orderBy('s.displayName', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** Dernier numéro attribué pour un préfixe donné (ex: "BTQ"), ou null si aucun. */
    public function findLastReferenceNumberByPrefix(string $prefix): ?string
    {
        return $this->createQueryBuilder('s')
            ->select('s.referenceNumber')
            ->andWhere('s.referenceNumber LIKE :prefix')
            ->setParameter('prefix', $prefix . '-%')
            ->orderBy('s.referenceNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()['referenceNumber'] ?? null;
    }

    /** Meilleurs vendeurs par ventes cumulées de leurs produits, avec exclusions de type optionnelles. */
    public function findAutoTopVendors(int $limit, bool $excludePro = false, bool $excludeBoutique = false): array
    {
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(p.sellerProfile) AS sellerId', 'SUM(p.salesCount) AS totalSales')
            ->from(\App\Entity\Product::class, 'p')
            ->andWhere('p.status = :status')
            ->setParameter('status', 'active')
            ->groupBy('p.sellerProfile')
            ->orderBy('totalSales', 'DESC')
            ->setMaxResults($limit * 4) // marge pour compenser les exclusions de type ci-dessous
            ->getQuery()
            ->getResult();

        $sellerIds = array_column($rows, 'sellerId');
        if (!$sellerIds) {
            return $this->findTopVendors($limit);
        }

        $sellers = $this->createQueryBuilder('s')
            ->andWhere('s.id IN (:ids)')
            ->andWhere('s.status = :status')
            ->setParameter('ids', $sellerIds)
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();

        $byId = [];
        foreach ($sellers as $seller) {
            $byId[$seller->getId()] = $seller;
        }

        $result = [];
        foreach ($sellerIds as $id) {
            $seller = $byId[$id] ?? null;
            if (!$seller) {
                continue;
            }
            if ($excludePro && $seller instanceof \App\Entity\ProProfile) {
                continue;
            }
            if ($excludeBoutique && $seller instanceof \App\Entity\StoreProfile) {
                continue;
            }
            $result[] = $seller;
            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
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

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.status = :status')
            ->setParameter('status', $status)
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