<?php

namespace App\Entity;

use App\Repository\VehicleModelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleModelRepository::class)]
class VehicleModel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Brand::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Brand $brand = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /** Nom alternatif (orthographe différente selon la source). */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name2 = null;

    /** 'moto' si coché, sinon null — null est interprété comme "auto" par défaut. */
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $type = null;

    /** @var Collection<int, VehicleVariant> */
    #[ORM\OneToMany(targetEntity: VehicleVariant::class, mappedBy: 'model')]
    private Collection $variants;

    /**
     * Motorisations rattachées directement au modèle (cas Moto, sans variante).
     * @var Collection<int, VehicleEngine>
     */
    #[ORM\OneToMany(targetEntity: VehicleEngine::class, mappedBy: 'model')]
    private Collection $engines;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->variants = new ArrayCollection();
        $this->engines = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): static
    {
        $this->brand = $brand;

        return $this;
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

    public function getName2(): ?string
    {
        return $this->name2;
    }

    public function setName2(?string $name2): static
    {
        $this->name2 = $name2;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /** null en base = Auto par défaut. */
    public function isMoto(): bool
    {
        return 'moto' === $this->type;
    }

    /** @return Collection<int, VehicleVariant> */
    public function getVariants(): Collection
    {
        return $this->variants;
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

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}