<?php

namespace App\Entity;

use App\Repository\LegalDocumentVersionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/** Une version figée d'un LegalDocument. Jamais modifiée après publication. */
#[ORM\Entity(repositoryClass: LegalDocumentVersionRepository::class)]
class LegalDocumentVersion
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: LegalDocument::class, inversedBy: 'versions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?LegalDocument $document = null;

    #[ORM\Column]
    private int $versionNumber = 1;

    #[ORM\Column(length: 20, options: ['default' => self::STATUS_DRAFT])]
    private string $status = self::STATUS_DRAFT;

    /** Résumé des changements, écrit par l'admin, affiché au client avant les articles détaillés. */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $changeSummary = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, LegalArticle> */
    #[ORM\OneToMany(mappedBy: 'version', targetEntity: LegalArticle::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $articles;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->articles = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getDocument(): ?LegalDocument { return $this->document; }
    public function setDocument(?LegalDocument $document): static { $this->document = $document; return $this; }

    public function getVersionNumber(): int { return $this->versionNumber; }
    public function setVersionNumber(int $v): static { $this->versionNumber = $v; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): static { $this->status = $v; return $this; }
    public function isDraft(): bool { return self::STATUS_DRAFT === $this->status; }
    public function isPublished(): bool { return self::STATUS_PUBLISHED === $this->status; }

    public function getChangeSummary(): ?string { return $this->changeSummary; }
    public function setChangeSummary(?string $v): static { $this->changeSummary = $v; return $this; }

    public function getPublishedAt(): ?\DateTimeImmutable { return $this->publishedAt; }
    public function setPublishedAt(?\DateTimeImmutable $v): static { $this->publishedAt = $v; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /** @return Collection<int, LegalArticle> */
    public function getArticles(): Collection { return $this->articles; }
    public function addArticle(LegalArticle $article): static
    {
        $article->setVersion($this);
        $this->articles->add($article);
        return $this;
    }
}