<?php

namespace App\Entity;

use App\Repository\NewItemsTabTargetedProductRepository;
use Doctrine\ORM\Mapping as ORM;

/** Un produit choisi manuellement pour un onglet "Nouveauté" en mode ciblé. */
#[ORM\Entity(repositoryClass: NewItemsTabTargetedProductRepository::class)]
#[ORM\Table(name: 'new_items_tab_targeted_product')]
class NewItemsTabTargetedProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: NewItemsTab::class, inversedBy: 'targetedProducts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?NewItemsTab $tab = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\Column]
    private int $position = 0;

    /** Coché = ce produit devient la grande carte de l'onglet. Un seul à la fois (voir le contrôleur). */
    #[ORM\Column(options: ['default' => false])]
    private bool $isBigCard = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTab(): ?NewItemsTab
    {
        return $this->tab;
    }

    public function setTab(?NewItemsTab $tab): static
    {
        $this->tab = $tab;
        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
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

    public function isBigCard(): bool
    {
        return $this->isBigCard;
    }

    public function setIsBigCard(bool $isBigCard): static
    {
        $this->isBigCard = $isBigCard;
        return $this;
    }
}
