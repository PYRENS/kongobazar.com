<?php

namespace App\Entity;

use App\Repository\PartCatalogEngineCompatibilityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartCatalogEngineCompatibilityRepository::class)]
class PartCatalogEngineCompatibility
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PartCatalogEntry::class, inversedBy: 'engineCompatibilities')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PartCatalogEntry $partCatalogEntry = null;

    #[ORM\ManyToOne(targetEntity: VehicleEngine::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?VehicleEngine $vehicleEngine = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPartCatalogEntry(): ?PartCatalogEntry
    {
        return $this->partCatalogEntry;
    }

    public function setPartCatalogEntry(?PartCatalogEntry $partCatalogEntry): static
    {
        $this->partCatalogEntry = $partCatalogEntry;
        return $this;
    }

    public function getVehicleEngine(): ?VehicleEngine
    {
        return $this->vehicleEngine;
    }

    public function setVehicleEngine(?VehicleEngine $vehicleEngine): static
    {
        $this->vehicleEngine = $vehicleEngine;
        return $this;
    }
}