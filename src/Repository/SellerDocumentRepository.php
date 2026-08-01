<?php
// src/Repository/SellerDocumentRepository.php
namespace App\Repository;

use App\Entity\SellerDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SellerDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SellerDocument::class);
    }
}