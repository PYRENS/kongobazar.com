<?php

namespace App\Entity;

use App\Repository\CategoryAttributeRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryAttributeRepository::class)]
class CategoryAttribute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: Characteristic::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Characteristic $characteristic = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(options: ['default' => true])]
    private bool $nullable = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $filterable = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $showOnCard = false;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $groupTag = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getCharacteristic(): ?Characteristic
    {
        return $this->characteristic;
    }

    public function setCharacteristic(?Characteristic $characteristic): static
    {
        $this->characteristic = $characteristic;
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

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function setNullable(bool $nullable): static
    {
        $this->nullable = $nullable;
        return $this;
    }

    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    public function setFilterable(bool $filterable): static
    {
        $this->filterable = $filterable;
        return $this;
    }

    public function isShowOnCard(): bool
    {
        return $this->showOnCard;
    }

    public function setShowOnCard(bool $showOnCard): static
    {
        $this->showOnCard = $showOnCard;
        return $this;
    }

    public function getGroupTag(): ?string
    {
        return $this->groupTag;
    }

    public function setGroupTag(?string $groupTag): static
    {
        $this->groupTag = $groupTag;
        return $this;
    }

    // --- Passerelles vers le catalogue global : tout le code existant qui lit
    // attr.name / attr.unit / attr.dataType / attr.options continue de fonctionner
    // sans aucune modification, Twig et PHP appellent ces getters de façon transparente.

    public function getName(): ?string
    {
        return $this->characteristic?->getName();
    }

    public function getUnit(): ?string
    {
        return $this->characteristic?->getUnit();
    }

    public function getDataType(): string
    {
        return $this->characteristic?->getDataType() ?? 'text';
    }

    /** @return Collection<int, CharacteristicOption> */
    public function getOptions(): Collection
    {
        return $this->characteristic->getOptions();
    }
}