<?php

namespace App\Entity;

use App\Repository\HeroSideAdsSettingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HeroSideAdsSettingRepository::class)]
class HeroSideAdsSetting
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1; // singleton

    #[ORM\Column(nullable: true)]
    private ?int $hideBelowWidth = 799;

    public function getId(): int
    {
        return $this->id;
    }

    public function getHideBelowWidth(): ?int
    {
        return $this->hideBelowWidth;
    }

    public function setHideBelowWidth(?int $width): static
    {
        $this->hideBelowWidth = $width;
        return $this;
    }
}