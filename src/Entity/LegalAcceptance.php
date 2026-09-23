<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/** Preuve d'acceptation par un client d'une version précise d'un document légal. Jamais supprimée. */
#[ORM\Entity]
#[ORM\UniqueConstraint(columns: ['user_id', 'version_id'])]
class LegalAcceptance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: LegalDocumentVersion::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?LegalDocumentVersion $version = null;

    #[ORM\Column]
    private \DateTimeImmutable $acceptedAt;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress = null;

    public function __construct()
    {
        $this->acceptedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getVersion(): ?LegalDocumentVersion { return $this->version; }
    public function setVersion(?LegalDocumentVersion $version): static { $this->version = $version; return $this; }

    public function getAcceptedAt(): \DateTimeImmutable { return $this->acceptedAt; }

    public function getIpAddress(): ?string { return $this->ipAddress; }
    public function setIpAddress(?string $v): static { $this->ipAddress = $v; return $this; }
}