<?php

namespace App\Entity;

use App\Repository\RecentlyViewedSettingRepository;
use Doctrine\ORM\Mapping as ORM;

/** Singleton — pilote la section "Consulté récemment" de l'accueil. */
#[ORM\Entity(repositoryClass: RecentlyViewedSettingRepository::class)]
class RecentlyViewedSetting
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1;

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(options: ['default' => 20])]
    private int $displayCount = 20;

    public function getId(): int { return $this->id; }

    public function isEnabled(): bool { return $this->enabled; }
    public function setEnabled(bool $v): static { $this->enabled = $v; return $this; }

    public function getDisplayCount(): int { return $this->displayCount; }
    public function setDisplayCount(int $v): static { $this->displayCount = $v; return $this; }
}