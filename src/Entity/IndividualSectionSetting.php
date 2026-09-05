<?php

namespace App\Entity;

use App\Repository\IndividualSectionSettingRepository;
use Doctrine\ORM\Mapping as ORM;

/** Singleton — interrupteur général de la section "Particulier" de l'accueil. */
#[ORM\Entity(repositoryClass: IndividualSectionSettingRepository::class)]
class IndividualSectionSetting
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
