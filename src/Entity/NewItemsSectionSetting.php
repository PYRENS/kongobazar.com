<?php

namespace App\Entity;

use App\Repository\NewItemsSectionSettingRepository;
use Doctrine\ORM\Mapping as ORM;

/** Singleton — interrupteur général de la section "Nouveauté" de l'accueil. */
#[ORM\Entity(repositoryClass: NewItemsSectionSettingRepository::class)]
class NewItemsSectionSetting
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
