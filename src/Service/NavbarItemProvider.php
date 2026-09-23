<?php

namespace App\Service;

use App\Entity\CustomMenuItem;
use App\Repository\CustomMenuItemRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Éléments du navbar principal (emplacement "header_main"). Les 4 éléments système sont créés
 * automatiquement au premier accès ; ils ne sont ni modifiables ni supprimables, seulement
 * réordonnables et masquables. Les autres sont des liens personnalisés gérés en admin.
 */
class NavbarItemProvider
{
    public const LOCATION = 'header_main';
    public const SPACE = 'public';

    public const SYSTEM_ITEMS = [
        'all_rayons' => 'Tous les rayons',
        'home' => 'Accueil',
        'catalogue' => 'Catalogue',
        'offers' => 'Offres',
    ];

    public function __construct(
        private readonly CustomMenuItemRepository $repository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** @return CustomMenuItem[] Tous les éléments (actifs ou non), dans l'ordre d'affichage. */
    public function getAllItems(): array
    {
        $items = $this->fetchAll();

        $missing = $this->missingSystemKeys($items);
        if ($missing) {
            $this->createMissingSystemItems($items, $missing);
            $items = $this->fetchAll();
        }

        return $items;
    }

    /** @return CustomMenuItem[] Éléments du navbar à afficher aussi dans le tiroir de menu mobile. */
    public function getMobileItems(): array
    {
        return array_values(array_filter(
            $this->getPublicItems(),
            static fn (CustomMenuItem $item) => $item->isShowInMobileMenu() && 'all_rayons' !== $item->getSystemKey()
        ));
    }

    /** @return CustomMenuItem[] Éléments à afficher sur le site public. */
    public function getPublicItems(): array
    {
        return array_values(array_filter(
            $this->getAllItems(),
            static fn (CustomMenuItem $item) => $item->isActive()
        ));
    }

    /** @return CustomMenuItem[] */
    private function fetchAll(): array
    {
        return $this->repository->createQueryBuilder('m')
            ->andWhere('m.location = :location')
            ->andWhere('m.targetSpace = :space')
            ->andWhere('m.parent IS NULL')
            ->setParameter('location', self::LOCATION)
            ->setParameter('space', self::SPACE)
            ->orderBy('m.position', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @param CustomMenuItem[] $items @return string[] */
    private function missingSystemKeys(array $items): array
    {
        $present = [];
        foreach ($items as $item) {
            if ($item->getSystemKey()) {
                $present[$item->getSystemKey()] = true;
            }
        }

        return array_values(array_diff(array_keys(self::SYSTEM_ITEMS), array_keys($present)));
    }

    /**
     * @param CustomMenuItem[] $items
     * @param string[]         $missing
     */
    private function createMissingSystemItems(array $items, array $missing): void
    {
        $hasSystemItems = count($missing) < count(self::SYSTEM_ITEMS);

        if (!$hasSystemItems) {
            // Première initialisation : les éléments système passent en tête (positions 1 à 4),
            // les liens personnalisés existants (ex. "Notre Réseau") suivent dans leur ordre actuel.
            foreach ($items as $item) {
                $item->setPosition(($item->getPosition() ?? 0) + 10);
            }
            $position = 1;
        } else {
            // Élément système manquant seulement : ajouté à la fin
            $max = 0;
            foreach ($items as $item) {
                $max = max($max, (int) $item->getPosition());
            }
            $position = $max + 1;
        }

        foreach ($missing as $key) {
            $item = (new CustomMenuItem())
                ->setLocation(self::LOCATION)
                ->setTargetSpace(self::SPACE)
                ->setLabel(self::SYSTEM_ITEMS[$key])
                ->setSystemKey($key)
                ->setActive(true)
                ->setOpenInNewTab(false)
                ->setPosition($position++);
            $this->em->persist($item);
        }

        $this->em->flush();
    }
}