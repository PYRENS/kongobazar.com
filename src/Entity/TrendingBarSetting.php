<?php

namespace App\Entity;

use App\Repository\TrendingBarSettingRepository;
use Doctrine\ORM\Mapping as ORM;

/** Singleton — active/désactive le bandeau "Tendances" du header (catégories les plus visitées). */
#[ORM\Entity(repositoryClass: TrendingBarSettingRepository::class)]
class TrendingBarSetting
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1;

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    public function getId(): int { return $this->id; }

    public function isEnabled(): bool { return $this->enabled; }
    public function setEnabled(bool $v): static { $this->enabled = $v; return $this; }
}
