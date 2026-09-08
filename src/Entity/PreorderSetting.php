<?php

namespace App\Entity;

use App\Repository\PreorderSettingRepository;
use Doctrine\ORM\Mapping as ORM;

/** Singleton — pourcentage minimum du prix à verser pour valider une précommande. */
#[ORM\Entity(repositoryClass: PreorderSettingRepository::class)]
class PreorderSetting
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1;

    #[ORM\Column(options: ['default' => 30])]
    private int $minPercent = 30;

    public function getId(): int { return $this->id; }

    public function getMinPercent(): int { return $this->minPercent; }
    public function setMinPercent(int $percent): static
    {
        $this->minPercent = max(1, min(100, $percent));
        return $this;
    }
}
