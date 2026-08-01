<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class RelayProfile extends SellerProfile
{
    #[ORM\Column(type: 'text')]
    private ?string $localAddressDetails = null;

    #[ORM\Column(nullable: true)]
    private ?int $dailyCapacity = null; // nombre de colis gérables par jour, indicatif

    public function getLocalAddressDetails(): ?string
    {
        return $this->localAddressDetails;
    }

    public function setLocalAddressDetails(string $details): static
    {
        $this->localAddressDetails = $details;
        return $this;
    }

    public function getDailyCapacity(): ?int
    {
        return $this->dailyCapacity;
    }

    public function setDailyCapacity(?int $capacity): static
    {
        $this->dailyCapacity = $capacity;
        return $this;
    }
}