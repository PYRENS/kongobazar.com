<?php

namespace App\Entity;

use App\Repository\MostViewedSettingRepository;
use Doctrine\ORM\Mapping as ORM;

/** Singleton — pilote la section "Les plus consultés" de l'accueil. */
#[ORM\Entity(repositoryClass: MostViewedSettingRepository::class)]
class MostViewedSetting
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1;

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $includeKbz = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $includeStore = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $includePro = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $includeIndividual = true;

    #[ORM\Column(options: ['default' => 20])]
    private int $displayCount = 20;

    public function getId(): int { return $this->id; }

    public function isEnabled(): bool { return $this->enabled; }
    public function setEnabled(bool $v): static { $this->enabled = $v; return $this; }

    public function isIncludeKbz(): bool { return $this->includeKbz; }
    public function setIncludeKbz(bool $v): static { $this->includeKbz = $v; return $this; }

    public function isIncludeStore(): bool { return $this->includeStore; }
    public function setIncludeStore(bool $v): static { $this->includeStore = $v; return $this; }

    public function isIncludePro(): bool { return $this->includePro; }
    public function setIncludePro(bool $v): static { $this->includePro = $v; return $this; }

    public function isIncludeIndividual(): bool { return $this->includeIndividual; }
    public function setIncludeIndividual(bool $v): static { $this->includeIndividual = $v; return $this; }

    public function getDisplayCount(): int { return $this->displayCount; }
    public function setDisplayCount(int $v): static { $this->displayCount = $v; return $this; }
}
