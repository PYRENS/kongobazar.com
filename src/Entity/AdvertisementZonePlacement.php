<?php

namespace App\Entity;

use App\Repository\AdvertisementZonePlacementRepository;
use Doctrine\ORM\Mapping as ORM;

/** Une ligne = une bannière affichée dans une zone précise, avec ses propres statistiques. Une même Advertisement peut avoir plusieurs placements (plusieurs zones). */
#[ORM\Entity(repositoryClass: AdvertisementZonePlacementRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_ad_zone', columns: ['advertisement_id', 'zone_key'])]
class AdvertisementZonePlacement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Advertisement::class, inversedBy: 'zonePlacements')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Advertisement $advertisement = null;

    #[ORM\Column(length: 50)]
    private ?string $zoneKey = null;

    #[ORM\Column]
    private int $impressionCount = 0;

    #[ORM\Column]
    private int $clickCount = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdvertisement(): ?Advertisement
    {
        return $this->advertisement;
    }

    public function setAdvertisement(?Advertisement $advertisement): static
    {
        $this->advertisement = $advertisement;
        return $this;
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

    public function getImpressionCount(): int
    {
        return $this->impressionCount;
    }

    public function incrementImpressionCount(): static
    {
        $this->impressionCount++;
        return $this;
    }

    public function getClickCount(): int
    {
        return $this->clickCount;
    }

    public function incrementClickCount(): static
    {
        $this->clickCount++;
        return $this;
    }
}