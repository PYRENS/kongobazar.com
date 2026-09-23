<?php

namespace App\Repository;

use App\Entity\LegalAcceptance;
use App\Entity\LegalDocumentVersion;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LegalAcceptanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LegalAcceptance::class);
    }

    /** @return LegalAcceptance[] Toutes les acceptations de ce client, les plus récentes en premier. */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('la')
            ->andWhere('la.user = :user')->setParameter('user', $user)
            ->orderBy('la.acceptedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function hasAccepted(User $user, LegalDocumentVersion $version): bool
    {
        return null !== $this->findOneBy(['user' => $user, 'version' => $version]);
    }

    /**
     * Documents bloquants d'un espace pour lesquels ce client n'a pas encore accepté la dernière
     * version publiée. @return LegalDocumentVersion[] la version en attente, une par document concerné.
     */
    public function findPendingVersionsForUser(User $user, string $targetSpace): array
    {
        $publishedVersions = $this->getEntityManager()->createQuery(
            'SELECT v FROM App\Entity\LegalDocumentVersion v
             JOIN v.document d
             WHERE v.status = :status
               AND d.targetSpace = :space
               AND d.requiresAcceptance = true'
        )
            ->setParameter('status', LegalDocumentVersion::STATUS_PUBLISHED)
            ->setParameter('space', $targetSpace)
            ->getResult();

        // Une version par document : la plus récente (numéro de version le plus élevé).
        $latestByDocument = [];
        foreach ($publishedVersions as $version) {
            $docId = $version->getDocument()->getId();
            if (!isset($latestByDocument[$docId]) || $version->getVersionNumber() > $latestByDocument[$docId]->getVersionNumber()) {
                $latestByDocument[$docId] = $version;
            }
        }

        return array_values(array_filter(
            $latestByDocument,
            fn ($version) => !$this->hasAccepted($user, $version)
        ));
    }
}