<?php

namespace App\Entity;

use App\Repository\PartCompatibilityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartCompatibilityRepository::class)]
class PartCompatibility
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PartListingDetails::class, inversedBy: 'compatibilities')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PartListingDetails $partListingDetails = null;

    #[ORM\ManyToOne(targetEntity: Brand::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Brand $brand = null;

    /** Code OEM spécifique à cette marque (peut différer du code générique de la pièce). */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $oemCode = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPartListingDetails(): ?PartListingDetails
    {
        return $this->partListingDetails;
    }

    public function setPartListingDetails(?PartListingDetails $partListingDetails): static
    {
        $this->partListingDetails = $partListingDetails;

        return $this;
    }

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getOemCode(): ?string
    {
        return $this->oemCode;
    }

    public function setOemCode(?string $oemCode): static
    {
        $this->oemCode = $oemCode;

        return $this;
    }
}