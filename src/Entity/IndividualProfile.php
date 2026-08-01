<?php

namespace App\Entity;

use App\Repository\IndividualProfileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IndividualProfileRepository::class)]
class IndividualProfile extends SellerProfile
{
    // Volontairement minimal — pas de champ métier propre pour l'instant.
    // Contrairement à StoreProfile (rccmNumber) ou ProProfile (serviceDescription),
    // un particulier n'a pas de justificatif professionnel à fournir.
    // L'id, comme tous les champs communs (user, status, documents, contracts...),
    // est déjà hérité de SellerProfile — ne pas le redéclarer ici.
}