<?php

namespace App\Service;

use App\Entity\SellerProfile;
use App\Repository\SellerProfileRepository;

/**
 * Génère le prochain numéro de référence unique pour un vendeur (ex: BTQ-0001, PRO-0002).
 * Format : {PREFIXE}-{4 chiffres, incrémental par préfixe}.
 */
class SellerReferenceNumberGenerator
{
    public function __construct(private readonly SellerProfileRepository $sellerProfileRepository)
    {
    }

    public function generateFor(SellerProfile $seller): string
    {
        $prefix = $seller->getReferencePrefix();
        $lastNumber = $this->sellerProfileRepository->findLastReferenceNumberByPrefix($prefix);

        $nextSuffix = $lastNumber
            ? ((int) substr($lastNumber, strlen($prefix) + 1)) + 1
            : 1;

        return sprintf('%s-%04d', $prefix, $nextSuffix);
    }
}
