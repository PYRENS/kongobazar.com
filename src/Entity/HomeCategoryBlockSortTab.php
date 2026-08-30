<?php

namespace App\Entity;

use App\Repository\HomeCategoryBlockSortTabRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une ligne = un onglet de tri (Meilleures ventes / Nouveaux articles / Vedettes / Tendance)
 * pour un bloc catégorie précis — ordre et nombre de produits réglables par bloc.
 */
#[ORM\Entity(repositoryClass: HomeCategoryBlockSortTabRepository::class)]
#[ORM\Table(name: 'home_category_block_sort_tab')]
class HomeCategoryBlockSortTab
{
    public const SORT_KEYS = [
        'best_sellers' => 'Meilleures ventes',
        'new_arrivals' => 'Nouveaux articles',
        'featured' => 'Vedettes',
        'trending' => 'Tendance',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: HomeCategoryBlockSetting::class, inversedBy: 'sortTabs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?HomeCategoryBlockSetting $block = null;

    #[ORM\Column(length: 20)]
    private string $sortKey = 'best_sellers';

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column(options: ['default' => 4])]
    private int $productCount = 4;

    #[ORM\Column(options: ['default' => true])]
    private bool $visible = true;

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

    public function getSortKey(): string
    {
        return $this->sortKey;
    }

    public function setSortKey(string $sortKey): static
    {
        $this->sortKey = $sortKey;
        return $this;
    }

    public function getLabel(): string
    {
        return self::SORT_KEYS[$this->sortKey] ?? $this->sortKey;
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

    public function getProductCount(): int
    {
        return $this->productCount;
    }

    public function setProductCount(int $productCount): static
    {
        $this->productCount = $productCount;
        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;
        return $this;
    }
}
