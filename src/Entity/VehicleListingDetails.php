<?php

namespace App\Entity;

use App\Repository\VehicleListingDetailsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleListingDetailsRepository::class)]
class VehicleListingDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'vehicleListingDetails', targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?Product $product = null;

    /** La motorisation précise choisie — porte déjà marque/modèle/variante/carburant/puissance. */
    #[ORM\ManyToOne(targetEntity: VehicleEngine::class)]
    private ?VehicleEngine $vehicleEngine = null;

    /** Année réelle du véhicule mis en vente (distincte de la période catalogue de la motorisation). */
    #[ORM\Column(nullable: true)]
    private ?int $modelYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $mileage = null;

    /* --- Champs Auto (nullable pour Moto) --- */
    #[ORM\Column(nullable: true)]
    private ?int $seats = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $steeringSide = null; // 'left' | 'right'

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $transmission = null; // 'manual' | 'automatic'

    /* --- Champs Moto (nullable pour Auto) --- */
    #[ORM\ManyToOne(targetEntity: LicenseType::class)]
    private ?LicenseType $licenseType = null;

    #[ORM\ManyToOne(targetEntity: MotorcycleType::class)]
    private ?MotorcycleType $motorcycleType = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): static
    {
        $this->product = $product;

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

    public function getModelYear(): ?int
    {
        return $this->modelYear;
    }

    public function setModelYear(?int $modelYear): static
    {
        $this->modelYear = $modelYear;

        return $this;
    }

    public function getMileage(): ?int
    {
        return $this->mileage;
    }

    public function setMileage(?int $mileage): static
    {
        $this->mileage = $mileage;

        return $this;
    }

    public function getSeats(): ?int
    {
        return $this->seats;
    }

    public function setSeats(?int $seats): static
    {
        $this->seats = $seats;

        return $this;
    }

    public function getSteeringSide(): ?string
    {
        return $this->steeringSide;
    }

    public function setSteeringSide(?string $steeringSide): static
    {
        $this->steeringSide = $steeringSide;

        return $this;
    }

    public function getTransmission(): ?string
    {
        return $this->transmission;
    }

    public function setTransmission(?string $transmission): static
    {
        $this->transmission = $transmission;

        return $this;
    }

    public function getLicenseType(): ?LicenseType
    {
        return $this->licenseType;
    }

    public function setLicenseType(?LicenseType $licenseType): static
    {
        $this->licenseType = $licenseType;

        return $this;
    }

    public function getMotorcycleType(): ?MotorcycleType
    {
        return $this->motorcycleType;
    }

    public function setMotorcycleType(?MotorcycleType $motorcycleType): static
    {
        $this->motorcycleType = $motorcycleType;

        return $this;
    }
}