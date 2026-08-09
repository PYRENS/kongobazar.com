<?php

namespace App\Entity;

use App\Repository\PartListingDetailsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartListingDetailsRepository::class)]
class PartListingDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'partListingDetails', targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: PartCatalogEntry::class)]
    private ?PartCatalogEntry $partCatalogEntry = null;

    /** Références OEM propres à la pièce (souvent plusieurs selon marché/facelift). */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $oemCodes = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ean = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $manufacturerRef = null;

    /** @var Collection<int, PartCompatibility> */
    #[ORM\OneToMany(targetEntity: PartCompatibility::class, mappedBy: 'partListingDetails', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $compatibilities;

    /** @var Collection<int, PartEngineCompatibility> */
    #[ORM\OneToMany(targetEntity: PartEngineCompatibility::class, mappedBy: 'partListingDetails', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $engineCompatibilities;

    public function __construct()
    {
        $this->compatibilities = new ArrayCollection();
        $this->engineCompatibilities = new ArrayCollection();
    }

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

    public function getPartCatalogEntry(): ?PartCatalogEntry
    {
        return $this->partCatalogEntry;
    }

    public function setPartCatalogEntry(?PartCatalogEntry $partCatalogEntry): static
    {
        $this->partCatalogEntry = $partCatalogEntry;
        return $this;
    }

    public function getOemCodes(): ?array
    {
        return $this->oemCodes;
    }

    public function setOemCodes(?array $oemCodes): static
    {
        $this->oemCodes = $oemCodes;

        return $this;
    }

    public function getEan(): ?string
    {
        return $this->ean;
    }

    public function setEan(?string $ean): static
    {
        $this->ean = $ean;

        return $this;
    }

    public function getManufacturerRef(): ?string
    {
        return $this->manufacturerRef;
    }

    public function setManufacturerRef(?string $manufacturerRef): static
    {
        $this->manufacturerRef = $manufacturerRef;

        return $this;
    }

    /** @return Collection<int, PartCompatibility> */
    public function getCompatibilities(): Collection
    {
        return $this->compatibilities;
    }

    public function addCompatibility(PartCompatibility $compatibility): static
    {
        if (!$this->compatibilities->contains($compatibility)) {
            $this->compatibilities->add($compatibility);
            $compatibility->setPartListingDetails($this);
        }

        return $this;
    }

    public function removeCompatibility(PartCompatibility $compatibility): static
    {
        $this->compatibilities->removeElement($compatibility);

        return $this;
    }

    /** @return Collection<int, PartEngineCompatibility> */
    public function getEngineCompatibilities(): Collection
    {
        return $this->engineCompatibilities;
    }

    public function addEngineCompatibility(PartEngineCompatibility $ec): static
    {
        if (!$this->engineCompatibilities->contains($ec)) {
            $this->engineCompatibilities->add($ec);
            $ec->setPartListingDetails($this);
        }
        return $this;
    }

    public function removeEngineCompatibility(PartEngineCompatibility $ec): static
    {
        $this->engineCompatibilities->removeElement($ec);
        return $this;
    }
}