<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function search(?string $term, string $sortField = 'id', string $sortDir = 'DESC'): array
    {
        $allowedSorts = ['id', 'email', 'lastName', 'createdAt', 'status'];
        if (!in_array($sortField, $allowedSorts, true)) {
            $sortField = 'id';
        }
        $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('u')->orderBy('u.' . $sortField, $sortDir);

        if ($term) {
            $qb->andWhere('u.email LIKE :term OR u.phone LIKE :term OR u.lastName LIKE :term')
            ->setParameter('term', '%' . $term . '%');
        }

        return $qb->setMaxResults(100)->getQuery()->getResult();
    }

    public function countByMonth(int $months = 6): array
    {
        $conn = $this->getEntityManager()->getConnection();
        return $conn->fetchAllAssociative(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total
            FROM user
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL :months MONTH)
            GROUP BY ym ORDER BY ym ASC",
            ['months' => $months]
        );
    }   
    
    public function countByUnitsAndSellerType(array $unitIds, ?string $sellerType): int
    {
        if (empty($unitIds)) {
            return 0;
        }

        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.id)')
            ->andWhere('u.administrativeUnit IN (:units)')
            ->setParameter('units', $unitIds);

        if (null === $sellerType) {
            // "Particulier" = aucun profil vendeur associé
            $qb->leftJoin(\App\Entity\SellerProfile::class, 's', 'WITH', 's.user = u.id')
            ->andWhere('s.id IS NULL');
        } else {
            $class = match ($sellerType) {
                'store' => \App\Entity\StoreProfile::class,
                'pro' => \App\Entity\ProProfile::class,
                'relay' => \App\Entity\RelayProfile::class,
                default => \App\Entity\SellerProfile::class,
            };
            $qb->join($class, 's', 'WITH', 's.user = u.id');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
