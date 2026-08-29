<?php

namespace App\Entity;

use App\Repository\ShareEventRepository;
use Doctrine\ORM\Mapping as ORM;

/** Une ligne par clic sur une icône de partage (Facebook, WhatsApp, X...) sur une page publique. */
#[ORM\Entity(repositoryClass: ShareEventRepository::class)]
#[ORM\Index(columns: ['entity_type', 'entity_id'], name: 'idx_share_entity')]
#[ORM\Index(columns: ['page_key'], name: 'idx_share_page_key')]
#[ORM\Index(columns: ['platform'], name: 'idx_share_platform')]
class ShareEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** 'product' | 'category' | 'seller' | 'static_page' — même convention que SeoOverride. */
    #[ORM\Column(length: 30)]
    private string $entityType = 'static_page';

    #[ORM\Column(nullable: true)]
    private ?int $entityId = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $pageKey = null;

    /** Libellé libre pour affichage admin (ex: titre du produit au moment du partage). */
    #[ORM\Column(length: 200, nullable: true)]
    private ?string $adminLabel = null;

    /** 'facebook' | 'whatsapp' | 'x' | 'copy_link' | 'native' */
    #[ORM\Column(length: 20)]
    private string $platform = 'facebook';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

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

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function setPlatform(string $platform): static
    {
        $this->platform = $platform;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
