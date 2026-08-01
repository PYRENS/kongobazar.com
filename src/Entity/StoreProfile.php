<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class StoreProfile extends SellerProfile
{
    #[ORM\Column(length: 150)]
    private ?string $storeName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $rccmNumber = null;

    public function getStoreName(): ?string
    {
        return $this->storeName;
    }

    public function setStoreName(string $storeName): static
    {
        $this->storeName = $storeName;
        return $this;
    }

    public function getRccmNumber(): ?string
    {
        return $this->rccmNumber;
    }

    public function setRccmNumber(?string $rccmNumber): static
    {
        $this->rccmNumber = $rccmNumber;
        return $this;
    }
}