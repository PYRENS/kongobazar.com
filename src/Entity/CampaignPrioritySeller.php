<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/** Une boutique / un vendeur Pro dont on met en avant N produits soldés sur la page d'une campagne. */
#[ORM\Entity]
#[ORM\Table(name: 'campaign_priority_seller')]
class CampaignPrioritySeller
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Campaign::class, inversedBy: 'prioritySellers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Campaign $campaign = null;

    #[ORM\ManyToOne(targetEntity: SellerProfile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?SellerProfile $sellerProfile = null;

    /** Nombre de produits soldés de ce vendeur à mettre en avant. */
    #[ORM\Column(options: ['default' => 4])]
    private int $productLimit = 4;

    #[ORM\Column]
    private int $position = 0;

    public function getId(): ?int { return $this->id; }

    public function getCampaign(): ?Campaign { return $this->campaign; }
    public function setCampaign(?Campaign $campaign): static { $this->campaign = $campaign; return $this; }

    public function getSellerProfile(): ?SellerProfile { return $this->sellerProfile; }
    public function setSellerProfile(?SellerProfile $sellerProfile): static { $this->sellerProfile = $sellerProfile; return $this; }

    public function getProductLimit(): int { return $this->productLimit; }
    public function setProductLimit(int $productLimit): static { $this->productLimit = max(1, min(50, $productLimit)); return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }
}