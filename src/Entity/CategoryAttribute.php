<?php

namespace App\Entity;

use App\Entity\CategoryAttributeOption;
use App\Repository\CategoryAttributeRepository;
use Doctrine\Common\Collections\ArrayCollection;
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

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    /** 'text' | 'number' | 'select' | 'boolean' */
    #[ORM\Column(length: 20)]
    private string $dataType = 'text';

    /** Unité pour les nombres, ex: "cm", "kg", "m²". */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $unit = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(options: ['default' => true])]
    private bool $nullable = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $filterable = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $showOnCard = false;

    /** Étiquette informelle de regroupement dans l'admin, ex: "auto". */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $groupTag = null;

    /** @var Collection<int, CategoryAttributeOption> */
    #[ORM\OneToMany(targetEntity: CategoryAttributeOption::class, mappedBy: 'categoryAttribute', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $options;

    public function __construct()
    {
        $this->options = new ArrayCollection();
    }

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }

    public function setDataType(string $dataType): static
    {
        $this->dataType = $dataType;
        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): static
    {
        $this->unit = $unit;
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

    /** @return Collection<int, CategoryAttributeOption> */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(CategoryAttributeOption $option): static
    {
        if (!$this->options->contains($option)) {
            $this->options->add($option);
            $option->setCategoryAttribute($this);
        }
        return $this;
    }

    public function removeOption(CategoryAttributeOption $option): static
    {
        $this->options->removeElement($option);
        return $this;
    }
}