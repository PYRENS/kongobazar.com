<?php

namespace App\Entity;

use App\Repository\ComingSoonSectionSettingRepository;
use Doctrine\ORM\Mapping as ORM;

/** Singleton — pilote la section "Prochainement" de l'accueil : interrupteur + titre libre du badge. */
#[ORM\Entity(repositoryClass: ComingSoonSectionSettingRepository::class)]
class ComingSoonSectionSetting
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1;

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(length: 100, options: ['default' => 'Prochainement'])]
    private string $title = 'Prochainement';

    public function getId(): int { return $this->id; }

    public function isEnabled(): bool { return $this->enabled; }
    public function setEnabled(bool $v): static { $this->enabled = $v; return $this; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $v): static { $this->title = $v; return $this; }
}
