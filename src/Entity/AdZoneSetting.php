<?php

namespace App\Entity;

use App\Repository\AdZoneSettingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdZoneSettingRepository::class)]
class AdZoneSetting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $zoneKey = null;

    /** 'random' | 'fixed' */
    #[ORM\Column(length: 20, options: ['default' => 'random'])]
    private string $mode = 'random';

    #[ORM\ManyToOne(targetEntity: Advertisement::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Advertisement $fixedAdvertisement = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getZoneKey(): ?string
    {
        return $this->zoneKey;
    }

    public function setZoneKey(string $zoneKey): static
    {
        $this->zoneKey = $zoneKey;
        return $this;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): static
    {
        $this->mode = $mode;
        return $this;
    }

    public function getFixedAdvertisement(): ?Advertisement
    {
        return $this->fixedAdvertisement;
    }

    public function setFixedAdvertisement(?Advertisement $fixedAdvertisement): static
    {
        $this->fixedAdvertisement = $fixedAdvertisement;
        return $this;
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