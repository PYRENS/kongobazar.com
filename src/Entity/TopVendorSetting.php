<?php

namespace App\Entity;

use App\Repository\TopVendorSettingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Singleton — pilote la section "Top Vendeur" de l'accueil.
 *
 * displayMode :
 *   'auto'     — le système cherche automatiquement les meilleurs vendeurs (ventes + note)
 *   'targeted' — l'admin choisit explicitement les vendeurs (targetedSellers, ordre = position d'ajout)
 */
#[ORM\Entity(repositoryClass: TopVendorSettingRepository::class)]
class TopVendorSetting
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1; // singleton : une seule ligne, id toujours 1

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(length: 20, options: ['default' => 'auto'])]
    private string $displayMode = 'auto';

    #[ORM\Column(options: ['default' => 4])]
    private int $displayCount = 4;

    /** Exclut entièrement les vendeurs de type "Pro" (mode auto uniquement). */
    #[ORM\Column(options: ['default' => false])]
    private bool $excludePro = false;

    /** Exclut entièrement les vendeurs de type "Boutique" (mode auto uniquement). */
    #[ORM\Column(options: ['default' => false])]
    private bool $excludeBoutique = false;

    /** Pour le mode "targeted" : vendeurs choisis (voir TopVendorTargetedSeller pour l'ordre). */
    #[ORM\OneToMany(mappedBy: 'setting', targetEntity: TopVendorTargetedSeller::class, orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $targetedSellers;

    public function __construct()
    {
        $this->targetedSellers = new ArrayCollection();
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

    public function getDisplayMode(): string
    {
        return $this->displayMode;
    }

    public function setDisplayMode(string $displayMode): static
    {
        $this->displayMode = $displayMode;
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

    public function isExcludePro(): bool
    {
        return $this->excludePro;
    }

    public function setExcludePro(bool $excludePro): static
    {
        $this->excludePro = $excludePro;
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

    /** @return Collection<int, TopVendorTargetedSeller> */
    public function getTargetedSellers(): Collection
    {
        return $this->targetedSellers;
    }
}
