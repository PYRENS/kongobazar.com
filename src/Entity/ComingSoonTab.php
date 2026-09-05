<?php

namespace App\Entity;

use App\Repository\ComingSoonTabRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un onglet de la section "Prochainement", choisi par cascade (n'importe quel niveau).
 * Un onglet "Voir tous" est toujours affiché en dernier automatiquement — jamais stocké ici.
 */
#[ORM\Entity(repositoryClass: ComingSoonTabRepository::class)]
#[ORM\Table(name: 'coming_soon_tab')]
class ComingSoonTab
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

    /** Produits (statut "futur") choisis un par un pour cet onglet. */
    #[ORM\OneToMany(mappedBy: 'tab', targetEntity: ComingSoonTabProduct::class, orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getCategory(): ?Category { return $this->category; }
    public function setCategory(?Category $category): static { $this->category = $category; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }

    /** @return Collection<int, ComingSoonTabProduct> */
    public function getProducts(): Collection { return $this->products; }
}
