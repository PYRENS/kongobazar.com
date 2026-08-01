<?php

namespace App\Entity;

use App\Repository\AdministrativeUnitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdministrativeUnitRepository::class)]
class AdministrativeUnit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $level = null; // 1 = Province, 2 = Ville/Territoire, 3 = Secteur/Commune, 4 = Groupement/Quartier

    #[ORM\Column(length: 180, unique: true)]
    private ?string $slug = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?self $parent = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    /** @var Collection<int, self> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $children;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $typeLabel = null; // "Province" | "Ville" | "Territoire" | "Commune" | "Secteur" | "Chefferie" | "Groupement" | "Quartier"

    public function __construct()
    {
        $this->children = new ArrayCollection(); 
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    /** @return Collection<int, self> */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    /**
     * Vérifie que cette entité ET toute sa lignée parentale sont actives.
     * Si un parent (province, ville...) est désactivé, aucun enfant ne peut
     * être "effectivement actif", même si son propre champ active = true.
     */
    public function isEffectivelyActive(): bool
    {
        if (!$this->active) {
            return false;
        }
        return null === $this->parent || $this->parent->isEffectivelyActive();
    }

    public function getTypeLabel(): ?string
    {
        return $this->typeLabel;
    }

    public function setTypeLabel(?string $typeLabel): static
    {
        $this->typeLabel = $typeLabel;
        return $this;
    }

    public function getAncestorAtLevel(int $level): ?self
    {
        if ($this->level === $level) {
            return $this;
        }
        $current = $this->parent;
        while (null !== $current) {
            if ($current->getLevel() === $level) {
                return $current;
            }
            $current = $current->getParent();
        }
        return null;
    }

    /**
     * Retourne les segments de localisation à afficher, en respectant la règle
     * spéciale Kinshasa (niveau 2 "Ville" omis car redondant avec le niveau 1).
     *
     * @return string[]
     */
    public function getLocalisationParts(bool $full = false): array
    {
        $level1 = $this->getAncestorAtLevel(1);
        $level2 = $this->getAncestorAtLevel(2);
        $level3 = $this->getAncestorAtLevel(3);
        $level4 = $this->getAncestorAtLevel(4);

        $isKinshasa = $level1 && $level1->getName() === 'Kinshasa';

        $parts = [];
        if ($level1) {
            $parts[] = $level1->getName();
        }

        if ($isKinshasa) {
            if ($level3) $parts[] = $level3->getName();
            if ($full && $level4) $parts[] = $level4->getName();
        } else {
            if ($level2) $parts[] = $level2->getName();
            if ($full) {
                if ($level3) $parts[] = $level3->getName();
                if ($level4) $parts[] = $level4->getName();
            }
        }

        return $parts;
    }


    public function getFullPath(): string
    {
        $parts = [$this->name];
        $walker = $this->parent;
        while ($walker) {
            array_unshift($parts, $walker->getName());
            $walker = $walker->getParent();
        }
        return implode(' / ', $parts);
    }

    public function getDescendantUnits(): array
    {
        $all = [$this];
        foreach ($this->children as $child) {
            $all = array_merge($all, $child->getDescendantUnits());
        }
        return $all;
    }

}
