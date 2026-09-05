<?php

namespace App\Entity;

use App\Repository\NewItemsTabRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une ligne = un onglet de la section "Nouveauté" (ex: "Furniture", "Clothings"...).
 *
 * mode :
 *   'auto'     — le système cherche automatiquement les nouveaux articles (hors vendeurs Particulier)
 *   'targeted' — l'admin choisit les produits un par un (voir NewItemsTabTargetedProduct)
 *
 * bigCardMode :
 *   'manual' — bigCardProduct choisi explicitement par l'admin
 *   'random' — tiré au hasard parmi les produits résolus pour cet onglet
 */
#[ORM\Entity(repositoryClass: NewItemsTabRepository::class)]
#[ORM\Table(name: 'new_items_tab')]
class NewItemsTab
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

    #[ORM\Column(length: 20, options: ['default' => 'auto'])]
    private string $mode = 'auto';

    #[ORM\Column(length: 20, options: ['default' => 'random'])]
    private string $bigCardMode = 'random';

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Product $bigCardProduct = null;

    /** Nombre total de produits affichés (1 grande carte + le reste en petites, 9 par défaut = 1 + 8). */
    #[ORM\Column(options: ['default' => 9])]
    private int $productCount = 9;

    /** Pour le mode "targeted" : produits choisis un par un pour cet onglet. */
    #[ORM\OneToMany(mappedBy: 'tab', targetEntity: NewItemsTabTargetedProduct::class, orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $targetedProducts;

    public function __construct()
    {
        $this->targetedProducts = new ArrayCollection();
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

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): static
    {
        $this->mode = $mode;
        return $this;
    }

    public function getBigCardMode(): string
    {
        return $this->bigCardMode;
    }

    public function setBigCardMode(string $bigCardMode): static
    {
        $this->bigCardMode = $bigCardMode;
        return $this;
    }

    public function getBigCardProduct(): ?Product
    {
        return $this->bigCardProduct;
    }

    public function setBigCardProduct(?Product $bigCardProduct): static
    {
        $this->bigCardProduct = $bigCardProduct;
        return $this;
    }

    public function getProductCount(): int
    {
        return $this->productCount;
    }

    public function setProductCount(int $productCount): static
    {
        $this->productCount = $productCount;
        return $this;
    }

    /** @return Collection<int, NewItemsTabTargetedProduct> */
    public function getTargetedProducts(): Collection
    {
        return $this->targetedProducts;
    }
}
