<?php

namespace App\Entity;

use App\Repository\HomeCategoryBlockSubcategoryRepository;
use Doctrine\ORM\Mapping as ORM;

/** Une ligne = une sous-catégorie affichée dans le menu de gauche d'un bloc, avec sa position propre à ce bloc (réordonnable). */
#[ORM\Entity(repositoryClass: HomeCategoryBlockSubcategoryRepository::class)]
#[ORM\Table(name: 'home_category_block_subcategory_item')]
class HomeCategoryBlockSubcategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: HomeCategoryBlockSetting::class, inversedBy: 'subcategoryItems')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?HomeCategoryBlockSetting $block = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column]
    private int $position = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBlock(): ?HomeCategoryBlockSetting
    {
        return $this->block;
    }

    public function setBlock(?HomeCategoryBlockSetting $block): static
    {
        $this->block = $block;
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
