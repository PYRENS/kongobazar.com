<?php

namespace App\Entity;

use App\Entity\RelayProfile;
use App\Entity\StoreProfile;
use App\Repository\SellerProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use App\Entity\Product;


#[ORM\Entity(repositoryClass: SellerProfileRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    'store' => StoreProfile::class,
    'pro' => ProProfile::class,
    'relay' => RelayProfile::class,
    'individual' => IndividualProfile::class,
])]
#[Vich\Uploadable]
abstract class SellerProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** @var Collection<int, Product> */
    #[ORM\OneToMany(mappedBy: 'sellerProfile', targetEntity: Product::class)]
    private Collection $products;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    private string $status = 'pending'; // pending | active | expired | deserteur

    /** Boutique/vendeur officiellement lié à ou appartenant à KongoBazar (par opposition aux boutiques/pro tiers). */
    #[ORM\Column(options: ['default' => false])]
    private bool $isKbz = false;

    /** Numéro de référence unique, attribué à la validation de l'inscription (ex: BTQ-0001, PRO-0001, PRT-0001, RLY-0001). */
    #[ORM\Column(length: 20, nullable: true, unique: true)]
    private ?string $referenceNumber = null;

    /** @var Collection<int, AdministrativeUnit> */
    #[ORM\ManyToMany(targetEntity: AdministrativeUnit::class)]
    #[ORM\JoinTable(name: 'seller_profile_delivery_zone')]
    private Collection $deliveryZones;

    #[ORM\OneToMany(mappedBy: 'sellerProfile', targetEntity: SellerDocument::class, orphanRemoval: true)]
    private Collection $documents;

    // Nouvelle propriété
    /** @var Collection<int, Contract> */
    #[ORM\OneToMany(mappedBy: 'sellerProfile', targetEntity: Contract::class, orphanRemoval: true)]
    private Collection $contracts;

    #[ORM\OneToOne(mappedBy: 'sellerProfile', targetEntity: License::class, cascade: ['persist', 'remove'])]
    private ?License $license = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'logoName')]
    private ?File $logoFile = null;

    #[ORM\Column(nullable: true)]
    private ?string $logoName = null;

    #[ORM\Column(length: 150)]
    private ?string $displayName = null;

    #[ORM\Column(length: 220, unique: true)]
    private ?string $slug = null;

    public function __construct() 
    {
        $this->deliveryZones = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->contracts = new ArrayCollection();
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
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

    public function isKbz(): bool
    {
        return $this->isKbz;
    }

    /** Libellé du type de vendeur, directement utilisable dans Twig (ex: product.sellerProfile.typeLabel). */
    public function getTypeLabel(): string
    {
        return match (true) {
            $this instanceof StoreProfile => 'Boutique',
            $this instanceof ProProfile => 'Vendeur Pro',
            $this instanceof RelayProfile => 'Point Relais',
            $this instanceof IndividualProfile => 'Particulier',
            default => 'Particulier',
        };
    }

    public function setIsKbz(bool $isKbz): static
    {
        $this->isKbz = $isKbz;
        return $this;
    }

    public function getReferenceNumber(): ?string
    {
        return $this->referenceNumber;
    }

    public function setReferenceNumber(?string $referenceNumber): static
    {
        $this->referenceNumber = $referenceNumber;
        return $this;
    }

    /** Préfixe utilisé pour générer le numéro de référence de ce type de vendeur. */
    public function getReferencePrefix(): string
    {
        return match (true) {
            $this instanceof StoreProfile => 'BTQ',
            $this instanceof ProProfile => 'PRO',
            $this instanceof RelayProfile => 'RLY',
            $this instanceof IndividualProfile => 'PRT',
            default => 'PRT',
        };
    }

    /** @return Collection<int, AdministrativeUnit> */
    public function getDeliveryZones(): Collection
    {
        return $this->deliveryZones;
    }

    public function addDeliveryZone(AdministrativeUnit $unit): static
    {
        if (!$this->deliveryZones->contains($unit)) {
            $this->deliveryZones->add($unit);
        }
        return $this;
    }

    public function removeDeliveryZone(AdministrativeUnit $unit): static
    {
        $this->deliveryZones->removeElement($unit);
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, SellerDocument> */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(SellerDocument $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setSellerProfile($this);
        }
        return $this;
    }

    public function removeDocument(SellerDocument $document): static
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getSellerProfile() === $this) {
                $document->setSellerProfile(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Contract> */
    public function getContracts(): Collection
    {
        return $this->contracts;
    }

    public function addContract(Contract $contract): static
    {
        if (!$this->contracts->contains($contract)) {
            $this->contracts->add($contract);
            $contract->setSellerProfile($this);
        }
        return $this;
    }

    public function removeContract(Contract $contract): static
    {
        if ($this->contracts->removeElement($contract)) {
            if ($contract->getSellerProfile() === $this) {
                $contract->setSellerProfile(null);
            }
        }
        return $this;
    }

    public function getLicense(): ?License
    {
        return $this->license;
    }

    public function setLicense(?License $license): static
    {
        $this->license = $license;
        return $this;
    }

    public function setLogoFile(?File $file = null): void
    {
        $this->logoFile = $file;
    }

    public function getLogoFile(): ?File
    {
        return $this->logoFile;
    }

    public function setLogoName(?string $name): void
    {
        $this->logoName = $name;
    }

    public function getLogoName(): ?string
    {
        return $this->logoName;
    }

    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): static
    {
        $this->displayName = $displayName;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

}