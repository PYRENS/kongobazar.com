<?php

namespace App\Entity;

use App\Repository\BrandRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: BrandRepository::class)]
#[Vich\Uploadable]
class Brand
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $name = null;

    /** Abréviation courte de la marque (ex: "BMW", "VW"). */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $sigle = null;

    #[ORM\Column(length: 120, unique: true)]
    private ?string $slug = null;

    #[ORM\Column]
    private ?bool $verified = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $premium = false;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    /**
     * Domaines d'usage de la marque (ex: ['auto'], ['moto'], ['auto', 'moto']).
     * Laissé à null pour les marques génériques (mode, électronique...).
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $type = null;

    #[ORM\ManyToOne(targetEntity: Pays::class, inversedBy: 'brands')]
    private ?Pays $pays = null;


    #[Vich\UploadableField(mapping: 'logos_brands', fileNameProperty: 'logoName')]
    private ?File $logoFile = null;

    #[ORM\Column(nullable: true)]
    private ?string $logoName = null;

    /** @var Collection<int, Product> */
    #[ORM\OneToMany(mappedBy: 'brand', targetEntity: Product::class)]
    private Collection $products;

    #[ORM\Column(options: ['default' => false])]
    private bool $featuredHomepage = false;

    #[ORM\Column(nullable: true)]
    private ?int $featuredHomepagePosition = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function isVerified(): ?bool
    {
        return $this->verified;
    }
    public function isPremium(): bool
    {
        return $this->premium;
    }

    public function setPremium(bool $premium): static
    {
        $this->premium = $premium;
        return $this;
    }

    public function setVerified(bool $verified): static
    {
        $this->verified = $verified;

        return $this;
    }

    public function getType(): ?array
    {
        return $this->type;
    }

    public function setType(?array $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function hasType(string $type): bool
    {
        return null !== $this->type && in_array($type, $this->type, true);
    }

    public function getSigle(): ?string
    {
        return $this->sigle;
    }

    public function setSigle(?string $sigle): static
    {
        $this->sigle = $sigle;

        return $this;
    }

    public function getPays(): ?Pays
    {
        return $this->pays;
    }

    public function setPays(?Pays $pays): static
    {
        $this->pays = $pays;

        return $this;
    }

    public function setLogoFile(?File $file = null): void
    {
        $this->logoFile = $file;
        if (null !== $file) {
            // Force Doctrine à détecter un changement réel sur l'entité,
            // sans quoi Vich ne déclenche jamais le traitement du fichier
            // si aucun autre champ n'a été modifié en même temps.
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
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

    /** @return Collection<int, Product> */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function isFeaturedHomepage(): bool
    {
        return $this->featuredHomepage;
    }

    public function setFeaturedHomepage(bool $featuredHomepage): static
    {
        $this->featuredHomepage = $featuredHomepage;

        return $this;
    }

    public function getFeaturedHomepagePosition(): ?int
    {
        return $this->featuredHomepagePosition;
    }

    public function setFeaturedHomepagePosition(?int $featuredHomepagePosition): static
    {
        $this->featuredHomepagePosition = $featuredHomepagePosition;

        return $this;
    }    
    
}
