<?php

namespace App\Entity;

use App\Repository\ComingSoonTabProductRepository;
use Doctrine\ORM\Mapping as ORM;

/** Un produit (statut "futur") choisi pour un onglet "Prochainement" précis. */
#[ORM\Entity(repositoryClass: ComingSoonTabProductRepository::class)]
#[ORM\Table(name: 'coming_soon_tab_product')]
class ComingSoonTabProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ComingSoonTab::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ComingSoonTab $tab = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\Column]
    private int $position = 0;

    public function getId(): ?int { return $this->id; }

    public function getTab(): ?ComingSoonTab { return $this->tab; }
    public function setTab(?ComingSoonTab $tab): static { $this->tab = $tab; return $this; }

    public function getProduct(): ?Product { return $this->product; }
    public function setProduct(?Product $product): static { $this->product = $product; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }
}
