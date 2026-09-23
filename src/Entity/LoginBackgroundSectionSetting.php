<?php

namespace App\Entity;

use App\Repository\LoginBackgroundSectionSettingRepository;
use Doctrine\ORM\Mapping as ORM;

/** Singleton — active/désactive l'affichage des photos de fond sur la page de connexion. */
#[ORM\Entity(repositoryClass: LoginBackgroundSectionSettingRepository::class)]
class LoginBackgroundSectionSetting
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