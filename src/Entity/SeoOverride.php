<?php

namespace App\Entity;

use App\Repository\SeoOverrideRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Surcharge SEO pour une page précise du site public.
 * "entityType" + "entityId" identifient une fiche (ex: 'product' + 42), "pageKey" identifie
 * une page statique/globale (ex: 'homepage', 'about', 'cgu') — l'un ou l'autre, jamais les deux.
 * Absence de ligne ici = valeurs générées automatiquement (voir SeoResolver).
 */
#[ORM\Entity(repositoryClass: SeoOverrideRepository::class)]
#[ORM\Index(columns: ['entity_type', 'entity_id'], name: 'idx_seo_entity')]
#[ORM\Index(columns: ['page_key'], name: 'idx_seo_page_key')]
#[Vich\Uploadable]
class SeoOverride
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** 'product' | 'category' | 'seller' | 'static_page' */
    #[ORM\Column(length: 30)]
    private string $entityType = 'static_page';

    #[ORM\Column(nullable: true)]
    private ?int $entityId = null;

    /** Pour entityType = 'static_page' : 'homepage', 'about', 'cgu', 'contact'... */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $pageKey = null;

    /** Libellé libre affiché dans la liste admin (ex: "Accueil", "iPhone 15 Pro Max"), sans logique métier. */
    #[ORM\Column(length: 200, nullable: true)]
    private ?string $adminLabel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $metaDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaKeywords = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ogTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $ogDescription = null;

    #[Vich\UploadableField(mapping: 'seo_og_images', fileNameProperty: 'ogImageName')]
    private ?File $ogImageFile = null;

    #[ORM\Column(nullable: true)]
    private ?string $ogImageName = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $noIndex = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $noFollow = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): static
    {
        $this->entityType = $entityType;
        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): static
    {
        $this->entityId = $entityId;
        return $this;
    }

    public function getPageKey(): ?string
    {
        return $this->pageKey;
    }

    public function setPageKey(?string $pageKey): static
    {
        $this->pageKey = $pageKey;
        return $this;
    }

    public function getAdminLabel(): ?string
    {
        return $this->adminLabel;
    }

    public function setAdminLabel(?string $adminLabel): static
    {
        $this->adminLabel = $adminLabel;
        return $this;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): static
    {
        $this->metaTitle = $metaTitle;
        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription;
        return $this;
    }

    public function getMetaKeywords(): ?string
    {
        return $this->metaKeywords;
    }

    public function setMetaKeywords(?string $metaKeywords): static
    {
        $this->metaKeywords = $metaKeywords;
        return $this;
    }

    public function getOgTitle(): ?string
    {
        return $this->ogTitle;
    }

    public function setOgTitle(?string $ogTitle): static
    {
        $this->ogTitle = $ogTitle;
        return $this;
    }

    public function getOgDescription(): ?string
    {
        return $this->ogDescription;
    }

    public function setOgDescription(?string $ogDescription): static
    {
        $this->ogDescription = $ogDescription;
        return $this;
    }

    public function setOgImageFile(?File $ogImageFile = null): static
    {
        $this->ogImageFile = $ogImageFile;
        if ($ogImageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getOgImageFile(): ?File
    {
        return $this->ogImageFile;
    }

    public function getOgImageName(): ?string
    {
        return $this->ogImageName;
    }

    public function setOgImageName(?string $ogImageName): static
    {
        $this->ogImageName = $ogImageName;
        return $this;
    }

    public function isNoIndex(): bool
    {
        return $this->noIndex;
    }

    public function setNoIndex(bool $noIndex): static
    {
        $this->noIndex = $noIndex;
        return $this;
    }

    public function isNoFollow(): bool
    {
        return $this->noFollow;
    }

    public function setNoFollow(bool $noFollow): static
    {
        $this->noFollow = $noFollow;
        return $this;
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
}
