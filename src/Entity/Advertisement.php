<?php

namespace App\Entity;

use App\Repository\AdvertisementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: AdvertisementRepository::class)]
#[Vich\Uploadable]
class Advertisement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // null = promo maison KongoBazar, sinon vendeur ayant acheté l'emplacement
    #[ORM\ManyToOne(targetEntity: SellerProfile::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?SellerProfile $advertiser = null;

    #[ORM\Column(length: 150)]
    private ?string $title = null; // libellé interne, pas forcément affiché

    #[ORM\Column(length: 200, unique: true, nullable: true)]
    private ?string $slug = null; // dérivé du titre + suffixe unique, utilisé dans l'URL de clic pour un aperçu de lien lisible

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null; // note libre pour l'admin uniquement, jamais affichée publiquement

    #[Vich\UploadableField(mapping: 'ad_banners', fileNameProperty: 'imageName')]
    private ?File $imageFile = null;

    #[ORM\Column(nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $targetUrl = null; // lien de destination au clic

    #[ORM\Column(options: ['default' => false])]
    private bool $openInNewTab = false;

    #[ORM\Column(length: 20)]
    private ?string $targetSpace = null; // public | pro | store | relay | manage

    #[ORM\Column(length: 50)]
    private ?string $zoneKey = null; // identifiant de l'emplacement (ex. "homepage_top", "sidebar_right")

    #[ORM\Column(nullable: true)]
    private ?int $position = null; // ordre d'affichage si plusieurs pubs dans la même zone

    #[ORM\Column]
    private ?\DateTimeImmutable $startAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endAt = null; // null = pas de date de fin, la pub reste active indéfiniment

    #[ORM\Column(length: 20)]
    private ?string $status = 'scheduled'; // scheduled | active | expired | paused

    #[ORM\Column]
    private ?int $clickCount = 0;

    #[ORM\Column]
    private ?int $impressionCount = 0;

    /** @var \Doctrine\Common\Collections\Collection<int, AdvertisementZonePlacement> */
    #[ORM\OneToMany(targetEntity: AdvertisementZonePlacement::class, mappedBy: 'advertisement', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private \Doctrine\Common\Collections\Collection $zonePlacements;

    #[ORM\Column]
    private ?bool $isPaid = false;

    // Renseigné uniquement si isPaid = true
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $priceAmountUsd = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Category $relatedCategory = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->zonePlacements = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdvertiser(): ?SellerProfile
    {
        return $this->advertiser;
    }

    public function setAdvertiser(?SellerProfile $advertiser): static
    {
        $this->advertiser = $advertiser;
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

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;
        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageName(?string $imageName): void
    {
        $this->imageName = $imageName;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function getTargetUrl(): ?string
    {
        return $this->targetUrl;
    }

    public function setTargetUrl(?string $targetUrl): static
    {
        $this->targetUrl = $targetUrl;
        return $this;
    }

    public function isOpenInNewTab(): bool
    {
        return $this->openInNewTab;
    }

    public function setOpenInNewTab(bool $openInNewTab): static
    {
        $this->openInNewTab = $openInNewTab;
        return $this;
    }

    public function getTargetSpace(): ?string
    {
        return $this->targetSpace;
    }

    public function setTargetSpace(string $targetSpace): static
    {
        $this->targetSpace = $targetSpace;

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

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeImmutable $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): ?\DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(?\DateTimeImmutable $endAt): static
    {
        $this->endAt = $endAt;

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

    public function getClickCount(): ?int
    {
        return $this->clickCount;
    }

    public function setClickCount(int $clickCount): static
    {
        $this->clickCount = $clickCount;

        return $this;
    }

    public function incrementClickCount(): static
    {
        $this->clickCount = ($this->clickCount ?? 0) + 1;

        return $this;
    }

    public function getImpressionCount(): ?int
    {
        return $this->impressionCount;
    }

    public function setImpressionCount(int $impressionCount): static
    {
        $this->impressionCount = $impressionCount;
        return $this;
    }

    public function incrementImpressionCount(): static
    {
        $this->impressionCount = ($this->impressionCount ?? 0) + 1;
        return $this;
    }

    public function isPaid(): ?bool
    {
        return $this->isPaid;
    }

    public function setIsPaid(bool $isPaid): static
    {
        $this->isPaid = $isPaid;

        return $this;
    }

    public function getPriceAmountUsd(): ?string
    {
        return $this->priceAmountUsd;
    }

    public function setPriceAmountUsd(?string $priceAmountUsd): static
    {
        $this->priceAmountUsd = $priceAmountUsd;

        return $this;
    }

    public function isCurrentlyActive(): bool
    {
        $now = new \DateTimeImmutable();
        return $this->status === 'active' && $this->startAt <= $now && (null === $this->endAt || $this->endAt > $now);
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return \Doctrine\Common\Collections\Collection<int, AdvertisementZonePlacement> */
    public function getZonePlacements(): \Doctrine\Common\Collections\Collection
    {
        return $this->zonePlacements;
    }

    public function addZonePlacement(AdvertisementZonePlacement $placement): static
    {
        if (!$this->zonePlacements->contains($placement)) {
            $this->zonePlacements->add($placement);
            $placement->setAdvertisement($this);
        }
        return $this;
    }

    public function removeZonePlacement(AdvertisementZonePlacement $placement): static
    {
        $this->zonePlacements->removeElement($placement);
        return $this;
    }

    public function getRelatedCategory(): ?Category
    {
        return $this->relatedCategory;
    }

    public function setRelatedCategory(?Category $relatedCategory): static
    {
        $this->relatedCategory = $relatedCategory;

        return $this;
    }


}
