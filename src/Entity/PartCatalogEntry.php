<?php

namespace App\Entity;

use App\Repository\PartCatalogEntryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartCatalogEntryRepository::class)]
class PartCatalogEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: Brand::class)]
    private ?Brand $brand = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ean = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $manufacturerRef = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $blocked = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /** 'pending_review' | 'validated' */
    #[ORM\Column(length: 20, options: ['default' => 'pending_review'])]
    private string $status = 'pending_review';

    #[ORM\Column(options: ['default' => false])]
    private bool $verified = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $complete = false;

    #[ORM\ManyToOne(targetEntity: SellerProfile::class)]
    private ?SellerProfile $createdBySeller = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /** @var Collection<int, PartCatalogOemCode> */
    #[ORM\OneToMany(targetEntity: PartCatalogOemCode::class, mappedBy: 'partCatalogEntry', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $oemCodes;

    /** @var Collection<int, PartCatalogBrandCompatibility> */
    #[ORM\OneToMany(targetEntity: PartCatalogBrandCompatibility::class, mappedBy: 'partCatalogEntry', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $brandCompatibilities;

    /** @var Collection<int, PartCatalogEngineCompatibility> */
    #[ORM\OneToMany(targetEntity: PartCatalogEngineCompatibility::class, mappedBy: 'partCatalogEntry', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $engineCompatibilities;

    /** @var Collection<int, PartCatalogAttributeValue> */
    #[ORM\OneToMany(targetEntity: PartCatalogAttributeValue::class, mappedBy: 'partCatalogEntry', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $attributeValues;

    /** @var Collection<int, PartCatalogImage> */
    #[ORM\OneToMany(targetEntity: PartCatalogImage::class, mappedBy: 'partCatalogEntry', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $images;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->oemCodes = new ArrayCollection();
        $this->brandCompatibilities = new ArrayCollection();
        $this->engineCompatibilities = new ArrayCollection();
        $this->attributeValues = new ArrayCollection();
        $this->images = new ArrayCollection();
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function isBlocked(): bool
    {
        return $this->blocked;
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

    public function setBlocked(bool $blocked): static
    {
        $this->blocked = $blocked;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): static
    {
        $this->verified = $verified;
        return $this;
    }

    public function isComplete(): bool
    {
        return $this->complete;
    }

    public function setComplete(bool $complete): static
    {
        $this->complete = $complete;
        return $this;
    }

    public function getCreatedBySeller(): ?SellerProfile
    {
        return $this->createdBySeller;
    }

    public function setCreatedBySeller(?SellerProfile $createdBySeller): static
    {
        $this->createdBySeller = $createdBySeller;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, PartCatalogOemCode> */
    public function getOemCodes(): Collection
    {
        return $this->oemCodes;
    }

    public function addOemCode(PartCatalogOemCode $code): static
    {
        if (!$this->oemCodes->contains($code)) {
            $this->oemCodes->add($code);
            $code->setPartCatalogEntry($this);
        }
        return $this;
    }

    public function removeOemCode(PartCatalogOemCode $code): static
    {
        $this->oemCodes->removeElement($code);
        return $this;
    }

    /** @return Collection<int, PartCatalogBrandCompatibility> */
    public function getBrandCompatibilities(): Collection
    {
        return $this->brandCompatibilities;
    }

    public function addBrandCompatibility(PartCatalogBrandCompatibility $c): static
    {
        if (!$this->brandCompatibilities->contains($c)) {
            $this->brandCompatibilities->add($c);
            $c->setPartCatalogEntry($this);
        }
        return $this;
    }

    public function removeBrandCompatibility(PartCatalogBrandCompatibility $c): static
    {
        $this->brandCompatibilities->removeElement($c);
        return $this;
    }

    /** @return Collection<int, PartCatalogEngineCompatibility> */
    public function getEngineCompatibilities(): Collection
    {
        return $this->engineCompatibilities;
    }

    public function addEngineCompatibility(PartCatalogEngineCompatibility $c): static
    {
        if (!$this->engineCompatibilities->contains($c)) {
            $this->engineCompatibilities->add($c);
            $c->setPartCatalogEntry($this);
        }
        return $this;
    }

    public function removeEngineCompatibility(PartCatalogEngineCompatibility $c): static
    {
        $this->engineCompatibilities->removeElement($c);
        return $this;
    }

    /** @return Collection<int, PartCatalogAttributeValue> */
    public function getAttributeValues(): Collection
    {
        return $this->attributeValues;
    }

    /** @return Collection<int, PartCatalogImage> */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(PartCatalogImage $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setPartCatalogEntry($this);
        }
        return $this;
    }

    public function removeImage(PartCatalogImage $image): static
    {
        $this->images->removeElement($image);
        return $this;
    }
}