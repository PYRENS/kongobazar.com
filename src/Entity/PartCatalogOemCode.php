<?php

namespace App\Entity;

use App\Repository\PartCatalogOemCodeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartCatalogOemCodeRepository::class)]
class PartCatalogOemCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PartCatalogEntry::class, inversedBy: 'oemCodes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PartCatalogEntry $partCatalogEntry = null;

    #[ORM\Column(length: 100)]
    private ?string $code = null;

    #[ORM\ManyToOne(targetEntity: Brand::class)]
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
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