<?php

namespace App\Entity;

use App\Repository\RayonFlyoutColumnItemRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un item (lien) à l'intérieur d'une colonne du flyout, choisi par cascade
 * (n'importe quel niveau, pas forcément un enfant direct de la colonne).
 * Si une colonne n'a aucun item, elle s'affiche comme un simple lien isolé.
 */
#[ORM\Entity(repositoryClass: RayonFlyoutColumnItemRepository::class)]
#[ORM\Table(name: 'rayon_flyout_column_item')]
class RayonFlyoutColumnItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: RayonFlyoutColumn::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?RayonFlyoutColumn $column = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column]
    private int $position = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getColumn(): ?RayonFlyoutColumn
    {
        return $this->column;
    }

    public function setColumn(?RayonFlyoutColumn $column): static
    {
        $this->column = $column;
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
}
