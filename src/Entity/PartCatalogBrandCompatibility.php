<?php

namespace App\Entity;

use App\Repository\PartCatalogBrandCompatibilityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartCatalogBrandCompatibilityRepository::class)]
class PartCatalogBrandCompatibility
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PartCatalogEntry::class, inversedBy: 'brandCompatibilities')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PartCatalogEntry $partCatalogEntry = null;

    #[ORM\ManyToOne(targetEntity: Brand::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Brand $brand = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPartCatalogEntry(): ?PartCatalogEntry
    {
        return $this->partCatalogEntry;
    }

    public function setPartCatalogEntry(?PartCatalogEntry $partCatalogEntry): static
    {
        $this->partCatalogEntry = $partCatalogEntry;
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
}