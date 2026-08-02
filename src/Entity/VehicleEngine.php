<?php

namespace App\Entity;

use App\Repository\VehicleEngineRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleEngineRepository::class)]
class VehicleEngine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Renseigné uniquement pour Auto (rattachement via Variante). */
    #[ORM\ManyToOne(targetEntity: VehicleVariant::class, inversedBy: 'engines')]
    private ?VehicleVariant $variant = null;

    /** Renseigné uniquement pour Moto (rattachement direct, sans Variante). */
    #[ORM\ManyToOne(targetEntity: VehicleModel::class, inversedBy: 'engines')]
    private ?VehicleModel $model = null;

    /** Libellé moteur, ex: "1.2 TCe" ou "101ccm" pour une moto. */
    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column(nullable: true)]
    private ?int $powerCv = null;

    #[ORM\Column(nullable: true)]
    private ?int $powerKw = null;

    /** Cylindrée en cm³, uniquement pour Moto. */
    #[ORM\Column(nullable: true)]
    private ?int $displacementCc = null;

    #[ORM\ManyToOne(targetEntity: FuelType::class, inversedBy: 'vehicleEngines')]
    private ?FuelType $fuelType = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $monthStart = null;

    #[ORM\Column(nullable: true)]
    private ?int $yearStart = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $monthEnd = null;

    #[ORM\Column(nullable: true)]
    private ?int $yearEnd = null;

    /** Caches dénormalisés — figent l'intitulé au moment de la saisie sur une annonce. */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $brandNameCache = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $modelNameCache = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $variantNameCache = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $periodLabel = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVariant(): ?VehicleVariant
    {
        return $this->variant;
    }

    public function setVariant(?VehicleVariant $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getModel(): ?VehicleModel
    {
        return $this->model;
    }

    public function setModel(?VehicleModel $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getPowerCv(): ?int
    {
        return $this->powerCv;
    }

    public function setPowerCv(?int $powerCv): static
    {
        $this->powerCv = $powerCv;

        return $this;
    }

    public function getPowerKw(): ?int
    {
        return $this->powerKw;
    }

    public function setPowerKw(?int $powerKw): static
    {
        $this->powerKw = $powerKw;

        return $this;
    }

    public function getDisplacementCc(): ?int
    {
        return $this->displacementCc;
    }

    public function setDisplacementCc(?int $displacementCc): static
    {
        $this->displacementCc = $displacementCc;

        return $this;
    }

    public function getFuelType(): ?FuelType
    {
        return $this->fuelType;
    }

    public function setFuelType(?FuelType $fuelType): static
    {
        $this->fuelType = $fuelType;

        return $this;
    }

    public function getMonthStart(): ?string
    {
        return $this->monthStart;
    }

    public function setMonthStart(?string $monthStart): static
    {
        $this->monthStart = $monthStart;

        return $this;
    }

    public function getYearStart(): ?int
    {
        return $this->yearStart;
    }

    public function setYearStart(?int $yearStart): static
    {
        $this->yearStart = $yearStart;

        return $this;
    }

    public function getMonthEnd(): ?string
    {
        return $this->monthEnd;
    }

    public function setMonthEnd(?string $monthEnd): static
    {
        $this->monthEnd = $monthEnd;

        return $this;
    }

    public function getYearEnd(): ?int
    {
        return $this->yearEnd;
    }

    public function setYearEnd(?int $yearEnd): static
    {
        $this->yearEnd = $yearEnd;

        return $this;
    }

    public function getBrandNameCache(): ?string
    {
        return $this->brandNameCache;
    }

    public function setBrandNameCache(?string $brandNameCache): static
    {
        $this->brandNameCache = $brandNameCache;

        return $this;
    }

    public function getModelNameCache(): ?string
    {
        return $this->modelNameCache;
    }

    public function setModelNameCache(?string $modelNameCache): static
    {
        $this->modelNameCache = $modelNameCache;

        return $this;
    }

    public function getVariantNameCache(): ?string
    {
        return $this->variantNameCache;
    }

    public function setVariantNameCache(?string $variantNameCache): static
    {
        $this->variantNameCache = $variantNameCache;

        return $this;
    }

    public function getPeriodLabel(): ?string
    {
        return $this->periodLabel;
    }

    public function setPeriodLabel(?string $periodLabel): static
    {
        $this->periodLabel = $periodLabel;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Libellé complet, ex: "Renault Captur Mk3 1.2 TCe (90 CV)" — omet
     * proprement le segment Variante si son nom est vide.
     */
    public function __toString(): string
    {
        $parts = array_filter([
            $this->brandNameCache,
            $this->modelNameCache,
            $this->variantNameCache,
            $this->label,
        ]);

        return implode(' ', $parts);
    }
}