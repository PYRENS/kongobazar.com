<?php

namespace App\Entity;

use App\Repository\BrandRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: BrandRepository::class)]
class Brand
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 120, unique: true)]
    private ?string $slug = null;

    #[ORM\Column]
    private ?bool $verified = null;


    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'logoName')]
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

    public function isVerified(): ?bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): static
    {
        $this->verified = $verified;

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
