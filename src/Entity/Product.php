<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Index(name: 'idx_product_status', columns: ['status'])]
#[ORM\Index(name: 'idx_product_condition', columns: ['condition'])]
#[ORM\Index(name: 'idx_product_created_at', columns: ['created_at'])]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductVariant::class, orphanRemoval: true)]
    private Collection $variants;

    #[ORM\ManyToOne(targetEntity: SellerProfile::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?SellerProfile $sellerProfile = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: Brand::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Brand $brand = null;

    #[ORM\Column(length: 3, options: ['default' => 'USD'])]
    private string $currency = 'USD'; // devise dans laquelle basePrice est exprimé

    // Prix de référence barré, affiché à côté du prix actuel si renseigné et supérieur à basePrice
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $compareAtPrice = null;

    #[ORM\Column(length: 200)]
    private ?string $title = null;

    #[ORM\Column(length: 220, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $ean = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $model = null;

    // Déclaration du vendeur, pas une certification KongoBazar. Valeurs : 'original' | 'replica' | null (non précisé).
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $authenticityStatus = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $basePrice = null;

    #[ORM\Column]
    private ?bool $negotiable = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $salesCount = 0;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductImage::class, orphanRemoval: true, cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $images;

    #[ORM\Column(options: ['default' => false])]
    private bool $featured = false;

    #[ORM\Column(nullable: true)]
    private ?int $shippingMinDays = null;

    #[ORM\Column(nullable: true)]
    private ?int $shippingMaxDays = null;

    // Nouvelle propriété (relation inverse, aucune colonne ajoutée en base)
    /** @var Collection<int, DiscountCampaign> */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: DiscountCampaign::class)]
    private Collection $discountCampaigns;

    #[ORM\Column(options: ['default' => false])]
    private bool $preorderEnabled = false;

    #[ORM\Column(name: '`condition`', length: 10, options: ['default' => 'new'])]
    private string $condition = 'new'; // 'new' | 'used'

    /** Stock du produit "à plat" (sans variante). Ignoré si le produit a des variantes — voir getEffectiveQuantity(). */
    #[ORM\Column(options: ['default' => 1])]
    private int $quantity = 1;

    #[ORM\OneToOne(mappedBy: 'product', targetEntity: VehicleListingDetails::class, cascade: ['persist', 'remove'])]
    private ?VehicleListingDetails $vehicleListingDetails = null;

    #[ORM\OneToOne(mappedBy: 'product', targetEntity: PartListingDetails::class, cascade: ['persist', 'remove'])]
    private ?PartListingDetails $partListingDetails = null;

    #[ORM\OneToOne(mappedBy: 'product', targetEntity: PropertyListingDetails::class, cascade: ['persist', 'remove'])]
    private ?PropertyListingDetails $propertyListingDetails = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->variants = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->discountCampaigns = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVehicleListingDetails(): ?VehicleListingDetails
    {
        return $this->vehicleListingDetails;
    }

    public function setVehicleListingDetails(?VehicleListingDetails $vehicleListingDetails): static
    {
        // met à jour le côté propriétaire de la relation
        if (null !== $vehicleListingDetails && $vehicleListingDetails->getProduct() !== $this) {
            $vehicleListingDetails->setProduct($this);
        }

        $this->vehicleListingDetails = $vehicleListingDetails;

        return $this;
    }

    public function getPartListingDetails(): ?PartListingDetails
    {
        return $this->partListingDetails;
    }

    public function setPartListingDetails(?PartListingDetails $partListingDetails): static
    {
        if (null !== $partListingDetails && $partListingDetails->getProduct() !== $this) {
            $partListingDetails->setProduct($this);
        }

        $this->partListingDetails = $partListingDetails;

        return $this;
    }

    public function getPropertyListingDetails(): ?PropertyListingDetails
    {
        return $this->propertyListingDetails;
    }

    public function setPropertyListingDetails(?PropertyListingDetails $propertyListingDetails): static
    {
        if (null !== $propertyListingDetails && $propertyListingDetails->getProduct() !== $this) {
            $propertyListingDetails->setProduct($this);
        }

        $this->propertyListingDetails = $propertyListingDetails;

        return $this;
    }

    public function getSellerProfile(): ?SellerProfile
    {
        return $this->sellerProfile;
    }

    public function setSellerProfile(?SellerProfile $sellerProfile): static
    {
        $this->sellerProfile = $sellerProfile;
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
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

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

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

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getAuthenticityStatus(): ?string
    {
        return $this->authenticityStatus;
    }

    public function setAuthenticityStatus(?string $authenticityStatus): static
    {
        $this->authenticityStatus = in_array($authenticityStatus, ['original', 'replica'], true) ? $authenticityStatus : null;

        return $this;
    }

    /**
     * Référence interne KongoBazar — dérivée de l'ID, jamais stockée en base.
     * Format : KBZ-000042 (6 chiffres minimum, s'élargit automatiquement au-delà, rien n'est tronqué).
     */
    public function getKongobazarReference(): ?string
    {
        return $this->id ? sprintf('KBZ-%06d', $this->id) : null;
    }

    public function getBasePrice(): ?string
    {
        return $this->basePrice;
    }

    public function setBasePrice(string $basePrice): static
    {
        $this->basePrice = $basePrice;

        return $this;
    }

    public function isNegotiable(): ?bool
    {
        return $this->negotiable;
    }

    public function setNegotiable(bool $negotiable): static
    {
        $this->negotiable = $negotiable;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    /** Stock réel à afficher/vérifier : somme des variantes si le produit en a, sinon le stock direct. */
    public function getEffectiveQuantity(): int
    {
        if (count($this->variants) > 0) {
            $sum = 0;
            foreach ($this->variants as $variant) {
                $sum += $variant->getQuantity();
            }
            return $sum;
        }

        return $this->quantity;
    }

    public function getVariants(): Collection
    {
        return $this->variants;
    }

    public function addVariant(ProductVariant $variant): static
    {
        if (!$this->variants->contains($variant)) {
            $this->variants->add($variant);
            $variant->setProduct($this);
        }
        return $this;
    }

    public function removeVariant(ProductVariant $variant): static
    {
        if ($this->variants->removeElement($variant)) {
            if ($variant->getProduct() === $this) {
                $variant->setProduct(null);
            }
        }
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getCompareAtPrice(): ?string
    {
        return $this->compareAtPrice;
    }

    public function setCompareAtPrice(?string $compareAtPrice): static
    {
        $this->compareAtPrice = $compareAtPrice;
        return $this;
    }

    public function isOnSale(): bool
    {
        return null !== $this->compareAtPrice
            && bccomp($this->compareAtPrice, $this->basePrice, 2) > 0;
    }

    public function getSalesCount(): int
    {
        return $this->salesCount;
    }

    public function setSalesCount(int $salesCount): static
    {
        $this->salesCount = $salesCount;
        return $this;
    }

    public function incrementSalesCount(int $by = 1): static
    {
        $this->salesCount += $by;
        return $this;
    }

    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(ProductImage $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setProduct($this);
        }
        return $this;
    }

    public function removeImage(ProductImage $image): static
    {
        if ($this->images->removeElement($image)) {
            if ($image->getProduct() === $this) {
                $image->setProduct(null);
            }
        }
        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): static
    {
        $this->featured = $featured;

        return $this;
    }

    public function getShippingMinDays(): ?int
    {
        return $this->shippingMinDays;
    }

    public function setShippingMinDays(?int $days): static
    {
        $this->shippingMinDays = $days;
        return $this;
    }

    public function getShippingMaxDays(): ?int
    {
        return $this->shippingMaxDays;
    }

    public function setShippingMaxDays(?int $days): static
    {
        $this->shippingMaxDays = $days;
        return $this;
    }

    public function getShippingDelayLabel(): ?string
    {
        if (null === $this->shippingMinDays || null === $this->shippingMaxDays) {
            return null;
        }
        return sprintf('%d-%d', $this->shippingMinDays, $this->shippingMaxDays);
    }

    public function getActiveDiscountCampaign(): ?DiscountCampaign
    {
        $now = new \DateTimeImmutable();
        foreach ($this->discountCampaigns as $campaign) {
            if ($campaign->isCurrentlyActive()) {
                return $campaign;
            }
        }
        return null;
    }

    public function getActiveDiscountEndAt(): ?\DateTimeImmutable
    {
        return $this->getActiveDiscountCampaign()?->getEndAt();
    }

    public function getActiveDiscountPercent(): ?int
    {
        $campaign = $this->getActiveDiscountCampaign();
        if (null === $campaign || (float) $this->basePrice <= 0) {
            return null;
        }
        return (int) round((1 - ((float) $campaign->getDiscountedPrice() / (float) $this->basePrice)) * 100);
    }

    public function getCurrentDiscountedPrice(): ?string
    {
        return $this->getActiveDiscountCampaign()?->getDiscountedPrice();
    }

    public function isRecent(): bool
    {
        $twoWeeksAgo = new \DateTimeImmutable('-14 days');
        return $this->createdAt >= $twoWeeksAgo;
    }

    public function isPreorderEnabled(): bool
    {
        return $this->preorderEnabled;
    }

    public function setPreorderEnabled(bool $preorderEnabled): static
    {
        $this->preorderEnabled = $preorderEnabled;
        return $this;
    }

    public function isComingSoon(): bool
    {
        return $this->status === 'coming_soon';
    }

    public function getCondition(): string
    {
        return $this->condition;
    }

    public function setCondition(string $condition): static
    {
        $this->condition = $condition;
        return $this;
    }

    public function isUsed(): bool
    {
        return $this->condition === 'used';
    }


}
