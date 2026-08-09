<?php

namespace App\Entity;

use App\Repository\ProductAttributeValueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductAttributeValueRepository::class)]
class ProductAttributeValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

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

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
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

    /** Valeur lisible, quel que soit le type de l'attribut. */
    public function getDisplayValue(): string
    {
        if ($this->categoryAttributeOption) {
            return $this->categoryAttributeOption->getLabel();
        }
        if (null !== $this->booleanValue) {
            return $this->booleanValue ? 'Oui' : 'Non';
        }
        if (null !== $this->numberValue) {
            $unit = $this->categoryAttribute?->getUnit();
            return $this->numberValue . ($unit ? ' ' . $unit : '');
        }
        return $this->textValue ?? '';
    }
}