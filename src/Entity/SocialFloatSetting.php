<?php

namespace App\Entity;

use App\Repository\SocialFloatSettingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SocialFloatSettingRepository::class)]
class SocialFloatSetting
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1; // singleton : une seule ligne, id toujours 1

    #[ORM\Column(options: ['default' => true])]
    private bool $showOnDesktop = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $showOnTablet = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $showOnMobile = false;

    public function getId(): int
    {
        return $this->id;
    }

    public function isShowOnDesktop(): bool
    {
        return $this->showOnDesktop;
    }

    public function setShowOnDesktop(bool $value): static
    {
        $this->showOnDesktop = $value;
        return $this;
    }

    public function isShowOnTablet(): bool
    {
        return $this->showOnTablet;
    }

    public function setShowOnTablet(bool $value): static
    {
        $this->showOnTablet = $value;
        return $this;
    }

    public function isShowOnMobile(): bool
    {
        return $this->showOnMobile;
    }

    public function setShowOnMobile(bool $value): static
    {
        $this->showOnMobile = $value;
        return $this;
    }
}