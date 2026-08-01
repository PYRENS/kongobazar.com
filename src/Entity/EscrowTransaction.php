<?php

namespace App\Entity;

use App\Repository\EscrowTransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EscrowTransactionRepository::class)]
class EscrowTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'RESTRICT')]
    private ?Order $order = null;

    // Montant total détenu en séquestre (équivalent USD, gelé — cohérent avec Order.totalAmountUsd)
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $amountHeldUsd = null;

    // Montant réellement libéré au vendeur (peut différer du montant détenu en cas de remboursement partiel)
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $amountReleasedUsd = null;

    #[ORM\Column(length: 20)]
    private string $status = 'held'; // held | released | refunded | disputed

    // Référence de la transaction Mobile Money/carte côté opérateur, pour la réconciliation webhook
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $providerReference = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $releasedAt = null;

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

    public function getAmountHeldUsd(): ?string
    {
        return $this->amountHeldUsd;
    }

    public function setAmountHeldUsd(string $amountHeldUsd): static
    {
        $this->amountHeldUsd = $amountHeldUsd;

        return $this;
    }

    public function getAmountReleasedUsd(): ?string
    {
        return $this->amountReleasedUsd;
    }

    public function setAmountReleasedUsd(?string $amountReleasedUsd): static
    {
        $this->amountReleasedUsd = $amountReleasedUsd;

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

    public function getProviderReference(): ?string
    {
        return $this->providerReference;
    }

    public function setProviderReference(?string $providerReference): static
    {
        $this->providerReference = $providerReference;

        return $this;
    }

    public function getReleasedAt(): ?\DateTimeImmutable
    {
        return $this->releasedAt;
    }

    public function setReleasedAt(?\DateTimeImmutable $releasedAt): static
    {
        $this->releasedAt = $releasedAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
