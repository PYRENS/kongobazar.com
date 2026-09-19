<?php

namespace App\Entity;

use App\Repository\PromoStripSettingRepository;
use Doctrine\ORM\Mapping as ORM;

/** Singleton — nombre de bannières affichées à la fois dans le bandeau promo. */
#[ORM\Entity(repositoryClass: PromoStripSettingRepository::class)]
class PromoStripSetting
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1;

    #[ORM\Column(options: ['default' => 3])]
    private int $displayCount = 3;

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    public function getId(): int { return $this->id; }

    public function getDisplayCount(): int { return $this->displayCount; }
    public function setDisplayCount(int $v): static { $this->displayCount = max(1, $v); return $this; }

    public function isEnabled(): bool { return $this->enabled; }
    public function setEnabled(bool $v): static { $this->enabled = $v; return $this; }
}
