<?php

namespace App\Entity;

use App\Repository\LegalDocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/** Un type de document légal (CGV, CGU, Politique de confidentialité...), avec ses versions successives. */
#[ORM\Entity(repositoryClass: LegalDocumentRepository::class)]
class LegalDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $code = null;

    #[ORM\Column(length: 150)]
    private ?string $label = null;

    /** Espace concerné : public (Acheteur), pro, store, relay, manage. */
    #[ORM\Column(length: 20, options: ['default' => 'public'])]
    private string $targetSpace = 'public';

    /** false = document informatif seulement, jamais bloquant (pas de re-consentement obligatoire). */
    #[ORM\Column(options: ['default' => true])]
    private bool $requiresAcceptance = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, LegalDocumentVersion> */
    #[ORM\OneToMany(mappedBy: 'document', targetEntity: LegalDocumentVersion::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $versions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->versions = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getCode(): ?string { return $this->code; }
    public function setCode(string $code): static { $this->code = $code; return $this; }

    public function getLabel(): ?string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }

    public function getTargetSpace(): string { return $this->targetSpace; }
    public function setTargetSpace(string $v): static { $this->targetSpace = $v; return $this; }

    public function isRequiresAcceptance(): bool { return $this->requiresAcceptance; }
    public function setRequiresAcceptance(bool $v): static { $this->requiresAcceptance = $v; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /** @return Collection<int, LegalDocumentVersion> */
    public function getVersions(): Collection { return $this->versions; }
}