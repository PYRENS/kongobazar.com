<?php

namespace App\Entity;

use App\Repository\CategoryAttributeOptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryAttributeOptionRepository::class)]
class CategoryAttributeOption
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CategoryAttribute::class, inversedBy: 'options')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CategoryAttribute $categoryAttribute = null;

    #[ORM\Column(length: 100)]
    private ?string $label = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    /** Code hexadécimal optionnel, pour afficher une pastille colorée si cette option est une couleur. */
    #[ORM\Column(length: 7, nullable: true)]
    private ?string $colorHex = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function getColorHex(): ?string
    {
        return $this->colorHex;
    }

    public function setColorHex(?string $colorHex): static
    {
        $this->colorHex = $colorHex;
        return $this;
    }

    public function __toString(): string
    {
        return $this->label ?? '';
    }
}