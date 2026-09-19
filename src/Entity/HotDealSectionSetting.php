<?php

namespace App\Entity;

use App\Repository\HotDealSectionSettingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Singleton — pilote la carte "Offre exceptionnelle" de la colonne gauche de l'accueil.
 * Produits épinglés par l'admin en tête, puis complétés automatiquement parmi les
 * ventes flash actives, triées par pourcentage de réduction décroissant.
 */
#[ORM\Entity(repositoryClass: HotDealSectionSettingRepository::class)]
class HotDealSectionSetting
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1;

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(options: ['default' => 5])]
    private int $displayCount = 5;

    /** @var Collection<int, Product> */
    #[ORM\ManyToMany(targetEntity: Product::class)]
    #[ORM\JoinTable(name: 'hot_deal_section_targeted_product')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $targetedProducts;

    public function __construct()
    {
        $this->targetedProducts = new ArrayCollection();
    }

    public function getId(): int { return $this->id; }

    public function isEnabled(): bool { return $this->enabled; }
    public function setEnabled(bool $v): static { $this->enabled = $v; return $this; }

    public function getDisplayCount(): int { return $this->displayCount; }
    public function setDisplayCount(int $v): static { $this->displayCount = max(1, $v); return $this; }

    /** @return Collection<int, Product> */
    public function getTargetedProducts(): Collection { return $this->targetedProducts; }

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
}
