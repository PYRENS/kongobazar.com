<?php

namespace App\Entity;

use App\Repository\AdminSidebarThemeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminSidebarThemeRepository::class)]
class AdminSidebarTheme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $name = null;

    #[ORM\Column(length: 30)]
    private ?string $bgColor = null;

    #[ORM\Column(length: 30)]
    private ?string $textColor = null;

    #[ORM\Column(length: 30)]
    private ?string $hoverBgColor = null;

    #[ORM\Column(length: 30)]
    private ?string $hoverTextColor = null;

    #[ORM\Column(length: 30)]
    private ?string $activeBgColor = null;

    #[ORM\Column(length: 30)]
    private ?string $activeTextColor = null;

    #[ORM\Column(length: 30)]
    private ?string $iconColor = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getBgColor(): ?string
    {
        return $this->bgColor;
    }

    public function setBgColor(string $bgColor): static
    {
        $this->bgColor = $bgColor;
        return $this;
    }

    public function getTextColor(): ?string
    {
        return $this->textColor;
    }

    public function setTextColor(string $textColor): static
    {
        $this->textColor = $textColor;
        return $this;
    }

    public function getHoverBgColor(): ?string
    {
        return $this->hoverBgColor;
    }

    public function setHoverBgColor(string $hoverBgColor): static
    {
        $this->hoverBgColor = $hoverBgColor;
        return $this;
    }

    public function getHoverTextColor(): ?string
    {
        return $this->hoverTextColor;
    }

    public function setHoverTextColor(string $hoverTextColor): static
    {
        $this->hoverTextColor = $hoverTextColor;
        return $this;
    }

    public function getActiveBgColor(): ?string
    {
        return $this->activeBgColor;
    }

    public function setActiveBgColor(string $activeBgColor): static
    {
        $this->activeBgColor = $activeBgColor;
        return $this;
    }

    public function getActiveTextColor(): ?string
    {
        return $this->activeTextColor;
    }

    public function setActiveTextColor(string $activeTextColor): static
    {
        $this->activeTextColor = $activeTextColor;
        return $this;
    }

    public function getIconColor(): ?string
    {
        return $this->iconColor;
    }

    public function setIconColor(string $iconColor): static
    {
        $this->iconColor = $iconColor;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}