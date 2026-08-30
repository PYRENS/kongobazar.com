<?php

namespace App\Entity;

use App\Repository\TrendingSectionSettingRepository;
use Doctrine\ORM\Mapping as ORM;

/** Singleton — interrupteur général de la section "Articles tendances" de l'accueil. */
#[ORM\Entity(repositoryClass: TrendingSectionSettingRepository::class)]
class TrendingSectionSetting
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1; // singleton : une seule ligne, id toujours 1

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

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
}
