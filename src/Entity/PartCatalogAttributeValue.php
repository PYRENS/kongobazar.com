<?php

namespace App\Entity;

use App\Repository\PartCatalogAttributeValueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartCatalogAttributeValueRepository::class)]
class PartCatalogAttributeValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PartCatalogEntry::class, inversedBy: 'attributeValues')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PartCatalogEntry $partCatalogEntry = null;

    #[ORM\ManyToOne(targetEntity: CategoryAttribute::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CategoryAttribute $categoryAttribute = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $textValue = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3, nullable: true)]
    private ?string $numberValue = null;

    #[ORM\Column(nullable: true)]
    private ?bool $booleanValue = null;

    #[ORM\ManyToOne(targetEntity: CategoryAttributeOption::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CategoryAttributeOption $categoryAttributeOption = null;

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

    public function getCategoryAttribute(): ?CategoryAttribute
    {
        return $this->categoryAttribute;
    }

    public function setCategoryAttribute(?CategoryAttribute $categoryAttribute): static
    {
        $this->categoryAttribute = $categoryAttribute;
        return $this;
    }

    public function getTextValue(): ?string
    {
        return $this->textValue;
    }

    public function setTextValue(?string $textValue): static
    {
        $this->textValue = $textValue;
        return $this;
    }

    public function getNumberValue(): ?string
    {
        return $this->numberValue;
    }

    public function setNumberValue(?string $numberValue): static
    {
        $this->numberValue = $numberValue;
        return $this;
    }

    public function getBooleanValue(): ?bool
    {
        return $this->booleanValue;
    }

    public function setBooleanValue(?bool $booleanValue): static
    {
        $this->booleanValue = $booleanValue;
        return $this;
    }

    public function getCategoryAttributeOption(): ?CategoryAttributeOption
    {
        return $this->categoryAttributeOption;
    }

    public function setCategoryAttributeOption(?CategoryAttributeOption $categoryAttributeOption): static
    {
        $this->categoryAttributeOption = $categoryAttributeOption;
        return $this;
    }
}