<?php

namespace App\Entity;

use App\Repository\TopCategorySectionSettingRepository;
use Doctrine\ORM\Mapping as ORM;

/** Singleton — interrupteur général de la section "Top Catégorie" de l'accueil. */
#[ORM\Entity(repositoryClass: TopCategorySectionSettingRepository::class)]
class TopCategorySectionSetting
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
