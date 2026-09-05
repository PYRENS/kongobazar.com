<?php

namespace App\Entity;

use App\Repository\IndividualSectionCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une catégorie de la section "Particulier" (choisie par cascade, n'importe quel niveau).
 * "Voir tout" est toujours affiché en dernier automatiquement — jamais stocké ici.
 */
#[ORM\Entity(repositoryClass: IndividualSectionCategoryRepository::class)]
#[ORM\Table(name: 'individual_section_category')]
class IndividualSectionCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column]
    private int $position = 0;

    /** Nombre de cartes affichées pour cette catégorie. */
    #[ORM\Column(options: ['default' => 8])]
    private int $cardCount = 8;

    /** Produits prioritaires choisis à la main — complétés automatiquement pour atteindre cardCount. */
    #[ORM\OneToMany(mappedBy: 'sectionCategory', targetEntity: IndividualSectionPriorityProduct::class, orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $priorityProducts;

    public function __construct()
    {
        $this->priorityProducts = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getCategory(): ?Category { return $this->category; }
    public function setCategory(?Category $category): static { $this->category = $category; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }

    public function getCardCount(): int { return $this->cardCount; }
    public function setCardCount(int $cardCount): static { $this->cardCount = $cardCount; return $this; }

    /** @return Collection<int, IndividualSectionPriorityProduct> */
    public function getPriorityProducts(): Collection { return $this->priorityProducts; }
}
