<?php

namespace App\Entity;

use App\Repository\HomeDealsSettingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Singleton — pilote l'affichage de la section "Ventes flash" de l'accueil.
 *
 * displayMode :
 *   'random'             — tirage aléatoire parmi toutes les ventes flash actives
 *   'kbz_only'           — uniquement les boutiques marquées isKbz=true
 *   'mixed'               — répartition programmée KBZ / autres, avec compensation automatique
 *   'targeted_stores'    — uniquement les boutiques/pro choisis dans targetedSellers
 *   'targeted_products'  — uniquement les produits choisis dans targetedProducts (ex-"personnalisé")
 */
#[ORM\Entity(repositoryClass: HomeDealsSettingRepository::class)]
class HomeDealsSetting
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1; // singleton : une seule ligne, id toujours 1

    /** Interrupteur général : la section "Ventes flash" de l'accueil s'affiche-t-elle du tout ? */
    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(options: ['default' => 10])]
    private int $displayCount = 10;

    #[ORM\Column(length: 30, options: ['default' => 'random'])]
    private string $displayMode = 'random';

    /** Pour le mode "mixed" : nombre visé de produits issus de boutiques KBZ. */
    #[ORM\Column(nullable: true)]
    private ?int $mixedKbzCount = null;

    /** Pour le mode "mixed" : nombre visé de produits issus des autres boutiques/pro. */
    #[ORM\Column(nullable: true)]
    private ?int $mixedOtherCount = null;

    /** Exclut entièrement les vendeurs de type "Boutique" (tous modes sauf ciblés). */
    #[ORM\Column(options: ['default' => false])]
    private bool $excludeBoutique = false;

    /** Exclut entièrement les vendeurs de type "Pro" (tous modes sauf ciblés). */
    #[ORM\Column(options: ['default' => false])]
    private bool $excludePro = false;

    /** Vendeurs explicitement exclus par recherche de nom, quel que soit le mode. */
    #[ORM\ManyToMany(targetEntity: SellerProfile::class)]
    #[ORM\JoinTable(name: 'home_deals_excluded_seller')]
    private Collection $excludedSellers;

    /** Pour le mode "targeted_stores" : boutiques/pro dont les produits en vente flash sont affichés. */
    #[ORM\ManyToMany(targetEntity: SellerProfile::class)]
    #[ORM\JoinTable(name: 'home_deals_targeted_seller')]
    private Collection $targetedSellers;

    /** Pour le mode "targeted_products" : produits choisis un par un. */
    #[ORM\ManyToMany(targetEntity: Product::class)]
    #[ORM\JoinTable(name: 'home_deals_targeted_product')]
    private Collection $targetedProducts;

    /** Pour le mode "category" : catégories dont les ventes flash actives sont affichées (descendants inclus). */
    #[ORM\ManyToMany(targetEntity: Category::class)]
    #[ORM\JoinTable(name: 'home_deals_targeted_category')]
    private Collection $targetedCategories;

    public function __construct()
    {
        $this->excludedSellers = new ArrayCollection();
        $this->targetedSellers = new ArrayCollection();
        $this->targetedProducts = new ArrayCollection();
        $this->targetedCategories = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function getDisplayCount(): int
    {
        return $this->displayCount;
    }

    public function setDisplayCount(int $displayCount): static
    {
        $this->displayCount = $displayCount;
        return $this;
    }

    public function getDisplayMode(): string
    {
        return $this->displayMode;
    }

    public function setDisplayMode(string $displayMode): static
    {
        $this->displayMode = $displayMode;
        return $this;
    }

    public function getMixedKbzCount(): ?int
    {
        return $this->mixedKbzCount;
    }

    public function setMixedKbzCount(?int $mixedKbzCount): static
    {
        $this->mixedKbzCount = $mixedKbzCount;
        return $this;
    }

    public function getMixedOtherCount(): ?int
    {
        return $this->mixedOtherCount;
    }

    public function setMixedOtherCount(?int $mixedOtherCount): static
    {
        $this->mixedOtherCount = $mixedOtherCount;
        return $this;
    }

    public function isExcludeBoutique(): bool
    {
        return $this->excludeBoutique;
    }

    public function setExcludeBoutique(bool $excludeBoutique): static
    {
        $this->excludeBoutique = $excludeBoutique;
        return $this;
    }

    public function isExcludePro(): bool
    {
        return $this->excludePro;
    }

    public function setExcludePro(bool $excludePro): static
    {
        $this->excludePro = $excludePro;
        return $this;
    }

    /** @return Collection<int, SellerProfile> */
    public function getExcludedSellers(): Collection
    {
        return $this->excludedSellers;
    }

    public function addExcludedSeller(SellerProfile $seller): static
    {
        if (!$this->excludedSellers->contains($seller)) {
            $this->excludedSellers->add($seller);
        }
        return $this;
    }

    public function removeExcludedSeller(SellerProfile $seller): static
    {
        $this->excludedSellers->removeElement($seller);
        return $this;
    }

    public function clearExcludedSellers(): static
    {
        $this->excludedSellers->clear();
        return $this;
    }

    /** @return Collection<int, SellerProfile> */
    public function getTargetedSellers(): Collection
    {
        return $this->targetedSellers;
    }

    public function addTargetedSeller(SellerProfile $seller): static
    {
        if (!$this->targetedSellers->contains($seller)) {
            $this->targetedSellers->add($seller);
        }
        return $this;
    }

    public function removeTargetedSeller(SellerProfile $seller): static
    {
        $this->targetedSellers->removeElement($seller);
        return $this;
    }

    public function clearTargetedSellers(): static
    {
        $this->targetedSellers->clear();
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

    /** @return Collection<int, Category> */
    public function getTargetedCategories(): Collection
    {
        return $this->targetedCategories;
    }

    public function addTargetedCategory(Category $category): static
    {
        if (!$this->targetedCategories->contains($category)) {
            $this->targetedCategories->add($category);
        }
        return $this;
    }

    public function removeTargetedCategory(Category $category): static
    {
        $this->targetedCategories->removeElement($category);
        return $this;
    }

    public function clearTargetedCategories(): static
    {
        $this->targetedCategories->clear();
        return $this;
    }
}
