<?php

namespace App\Entity;

use App\Repository\TopVendorTargetedSellerRepository;
use Doctrine\ORM\Mapping as ORM;

/** Une ligne = un vendeur choisi manuellement pour le mode "ciblé", avec sa position d'affichage. */
#[ORM\Entity(repositoryClass: TopVendorTargetedSellerRepository::class)]
#[ORM\Table(name: 'top_vendor_targeted_seller')]
class TopVendorTargetedSeller
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TopVendorSetting::class, inversedBy: 'targetedSellers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?TopVendorSetting $setting = null;

    #[ORM\ManyToOne(targetEntity: SellerProfile::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?SellerProfile $seller = null;

    #[ORM\Column]
    private int $position = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSetting(): ?TopVendorSetting
    {
        return $this->setting;
    }

    public function setSetting(?TopVendorSetting $setting): static
    {
        $this->setting = $setting;
        return $this;
    }

    public function getSeller(): ?SellerProfile
    {
        return $this->seller;
    }

    public function setSeller(?SellerProfile $seller): static
    {
        $this->seller = $seller;
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
