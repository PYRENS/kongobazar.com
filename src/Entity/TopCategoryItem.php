<?php

namespace App\Entity;

use App\Repository\TopCategoryItemRepository;
use Doctrine\ORM\Mapping as ORM;

/** Une ligne = une catégorie illustrée dans le carrousel "Top Catégorie" de l'accueil. */
#[ORM\Entity(repositoryClass: TopCategoryItemRepository::class)]
#[ORM\Table(name: 'top_category_item')]
class TopCategoryItem
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

    /** Couleur de fond du rond, propre à cet item — si vide, on retombe sur la couleur de la catégorie (voir getEffectiveBackgroundColor()). */
    #[ORM\Column(length: 7, nullable: true)]
    private ?string $backgroundColor = null;

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

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function setBackgroundColor(?string $backgroundColor): static
    {
        $this->backgroundColor = $backgroundColor;
        return $this;
    }

    /** Sa couleur propre si choisie, sinon celle (avec repli parent inclus) de sa catégorie, sinon le bleu du site. */
    public function getEffectiveBackgroundColor(): string
    {
        if ($this->backgroundColor) {
            return $this->backgroundColor;
        }

        return $this->category?->getEffectiveThemeColor() ?? '#2FA8E0';
    }
}
