<?php

namespace App\Entity;

use App\Repository\VehicleVariantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleVariantRepository::class)]
class VehicleVariant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: VehicleModel::class, inversedBy: 'variants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?VehicleModel $model = null;

    /** Peut être vide quand la source ne donne rien de plus que le modèle. */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 2)]
    private ?string $monthBegin = null;

    #[ORM\Column]
    private ?int $yearBegin = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $monthEnd = null;

    #[ORM\Column(nullable: true)]
    private ?int $yearEnd = null;

    /** @var Collection<int, VehicleEngine> */
    #[ORM\OneToMany(targetEntity: VehicleEngine::class, mappedBy: 'variant')]
    private Collection $engines;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->engines = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getMonthBegin(): ?string
    {
        return $this->monthBegin;
    }

    public function setMonthBegin(string $monthBegin): static
    {
        $this->monthBegin = $monthBegin;

        return $this;
    }

    public function getYearBegin(): ?int
    {
        return $this->yearBegin;
    }

    public function setYearBegin(int $yearBegin): static
    {
        $this->yearBegin = $yearBegin;

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

    /** @return Collection<int, VehicleEngine> */
    public function getEngines(): Collection
    {
        return $this->engines;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** Libellé humain, ex: "Mk3" ou "-" si le nom est vide. */
    public function __toString(): string
    {
        return $this->name ?? '-';
    }
}