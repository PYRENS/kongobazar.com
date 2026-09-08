<?php

namespace App\Entity;

use App\Repository\SidebarNewArrivalsSettingRepository;
use Doctrine\ORM\Mapping as ORM;

/** Singleton — pilote la section "Nouveaux articles" de la colonne gauche de l'accueil. */
#[ORM\Entity(repositoryClass: SidebarNewArrivalsSettingRepository::class)]
class SidebarNewArrivalsSetting
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1;

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(options: ['default' => 8])]
    private int $displayCount = 8;

    public function getId(): int { return $this->id; }

    public function isEnabled(): bool { return $this->enabled; }
    public function setEnabled(bool $v): static { $this->enabled = $v; return $this; }

    public function getDisplayCount(): int { return $this->displayCount; }
    public function setDisplayCount(int $v): static { $this->displayCount = $v; return $this; }
}
