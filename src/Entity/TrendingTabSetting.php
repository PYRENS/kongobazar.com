<?php

namespace App\Entity;

use App\Repository\TrendingTabSettingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une ligne = un onglet de la section "Articles tendances" de l'accueil.
 *
 * mode :
 *   'recent'        — produits les plus récents de la catégorie (comportement d'origine)
 *   'best_sellers'  — meilleures ventes de la catégorie
 *   'random'        — tirage aléatoire parmi les produits actifs de la catégorie
 *   'targeted'      — produits choisis un par un par l'admin (targetedProducts)
 */
#[ORM\Entity(repositoryClass: TrendingTabSettingRepository::class)]
#[ORM\Table(name: 'trending_tab_setting')]
class TrendingTabSetting
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

    #[ORM\Column(length: 20, options: ['default' => 'recent'])]
    private string $mode = 'recent';

    #[ORM\Column(options: ['default' => 5])]
    private int $productCount = 5;

    /** Pour le mode "targeted" : produits choisis un par un pour cet onglet précis. */
    #[ORM\ManyToMany(targetEntity: Product::class)]
    #[ORM\JoinTable(name: 'trending_tab_targeted_product')]
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

    public function getProductCount(): int
    {
        return $this->productCount;
    }

    public function setProductCount(int $productCount): static
    {
        $this->productCount = $productCount;
        return $this;
    }

    /** @return Collection<int, Product> */
    public function getTargetedProducts(): Collection
    {
        return $this->targetedProducts;
    }

    public function addTargetedProduct(Product $product): static
    {
        if (!$this->targetedProducts->contains($product)) {
            $this->targetedProducts->add($product);
        }
        return $this;
    }

    public function removeTargetedProduct(Product $product): static
    {
        $this->targetedProducts->removeElement($product);
        return $this;
    }

    public function clearTargetedProducts(): static
    {
        $this->targetedProducts->clear();
        return $this;
    }
}
