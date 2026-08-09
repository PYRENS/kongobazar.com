<?php

namespace App\Entity;

use App\Repository\PartEngineCompatibilityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartEngineCompatibilityRepository::class)]
class PartEngineCompatibility
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PartListingDetails::class, inversedBy: 'engineCompatibilities')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PartListingDetails $partListingDetails = null;

    #[ORM\ManyToOne(targetEntity: VehicleEngine::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?VehicleEngine $vehicleEngine = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPartListingDetails(): ?PartListingDetails
    {
        return $this->partListingDetails;
    }

    public function setPartListingDetails(?PartListingDetails $partListingDetails): static
    {
        $this->partListingDetails = $partListingDetails;
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