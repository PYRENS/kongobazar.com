<?php

namespace App\Entity;

use App\Repository\DisputeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DisputeRepository::class)]
class Dispute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'RESTRICT')]
    private ?Order $order = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?User $openedBy = null; // l'acheteur, dans le cas d'un refus de colis

    #[ORM\Column(type: 'text')]
    private ?string $reason = null;

    #[ORM\Column(length: 20)]
    private string $status = 'open'; // open | awaiting_seller | resolved

    // Décision du vendeur, une fois tranchée
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $resolutionType = null; // exchange | restock | cancellation

    // Rempli uniquement si resolutionType = cancellation
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $compensationType = null; // refund | store_credit

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $arbitratedBy = null; // modérateur ayant supervisé/tranché si escaladé

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

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

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;
        return $this;
    }

    public function getOpenedBy(): ?User
    {
        return $this->openedBy;
    }

    public function setOpenedBy(?User $openedBy): static
    {
        $this->openedBy = $openedBy;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

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

    public function getResolutionType(): ?string
    {
        return $this->resolutionType;
    }

    public function setResolutionType(?string $resolutionType): static
    {
        $this->resolutionType = $resolutionType;

        return $this;
    }

    public function getCompensationType(): ?string
    {
        return $this->compensationType;
    }

    public function setCompensationType(?string $compensationType): static
    {
        $this->compensationType = $compensationType;

        return $this;
    }

    public function getArbitratedBy(): ?User
    {
        return $this->arbitratedBy;
    }

    public function setArbitratedBy(?User $arbitratedBy): static
    {
        $this->arbitratedBy = $arbitratedBy;
        return $this;
    }

    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?\DateTimeImmutable $resolvedAt): static
    {
        $this->resolvedAt = $resolvedAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

}
