<?php

namespace App\Repository;

use App\Entity\AdminSidebarTheme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdminSidebarTheme>
 */
class AdminSidebarThemeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminSidebarTheme::class);
    }

    /**
     * @return AdminSidebarTheme[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Nombre d'admins utilisant actuellement ce thème (pour avertir avant suppression).
     */
    public function countUsersUsingTheme(int $themeId): int
    {
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(\App\Entity\User::class, 'u')
            ->where('u.adminSidebarTheme = :themeId')
            ->setParameter('themeId', $themeId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}