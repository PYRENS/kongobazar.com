<?php

namespace App\Entity;

use App\Repository\HomeCategoryCarouselItemRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une ligne = une catégorie feuille choisie pour le carrousel "Catégorie" de l'accueil
 * (sans titre, juste sous le Hero). Si aucune ligne n'existe, l'accueil bascule en mode
 * automatique : toutes les feuilles actives ayant au moins un produit actif.
 */
#[ORM\Entity(repositoryClass: HomeCategoryCarouselItemRepository::class)]
#[ORM\Table(name: 'home_category_carousel_item')]
#[ORM\UniqueConstraint(name: 'uniq_home_cat_carousel_category', columns: ['category_id'])]
class HomeCategoryCarouselItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Category $category = null;

    #[ORM\Column]
    private int $position = 0;

    /** Désactivée = reste dans la liste admin mais n'est plus affichée sur l'accueil. */
    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

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

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }
}