<?php

namespace App\Entity;

use App\Repository\RayonFlyoutColumnRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une ligne = une colonne affichée dans le flyout d'un rayon (sidebar "Top Rayons").
 * La catégorie choisie peut être à n'importe quel niveau sous le rayon (pas seulement
 * un enfant direct), contrairement à l'ancien système Category::flyoutColumnFeatured.
 *
 * Si items est vide, la colonne s'affiche comme un simple lien isolé.
 * Si items contient au moins un élément, elle devient une vraie colonne (titre + liste).
 */
#[ORM\Entity(repositoryClass: RayonFlyoutColumnRepository::class)]
#[ORM\Table(name: 'rayon_flyout_column')]
class RayonFlyoutColumn
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Category $rayon = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\OneToMany(mappedBy: 'column', targetEntity: RayonFlyoutColumnItem::class, orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRayon(): ?Category
    {
        return $this->rayon;
    }

    public function setRayon(?Category $rayon): static
    {
        $this->rayon = $rayon;
        return $this;
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

    /** @return Collection<int, RayonFlyoutColumnItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }
}
