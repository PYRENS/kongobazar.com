<?php

namespace App\Repository;

use App\Entity\LoginBackgroundImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LoginBackgroundImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginBackgroundImage::class);
    }

    /** @return LoginBackgroundImage[] Pour la liste admin, les plus récentes en premier. */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Une image active tirée au hasard, ou null s'il n'y en a aucune. */
    public function pickActiveRandom(): ?LoginBackgroundImage
    {
        $images = $this->createQueryBuilder('i')
            ->andWhere('i.active = true')
            ->getQuery()
            ->getResult();

        return $images ? $images[array_rand($images)] : null;
    }
}