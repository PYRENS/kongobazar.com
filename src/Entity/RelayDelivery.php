<?php

namespace App\Entity;

use App\Entity\RelayProfile;
use App\Repository\RelayDeliveryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RelayDeliveryRepository::class)]
class RelayDelivery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'RESTRICT')]
    private ?Order $order = null;

    #[ORM\ManyToOne(targetEntity: RelayProfile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?RelayProfile $relayProfile = null;

    // Code unique envoyé par SMS/notification à l'acheteur, présenté au point relais
    #[ORM\Column(length: 10, unique: true)]
    private ?string $pickupCode = null;

    #[ORM\Column(length: 20)]
    private string $status = 'awaiting_shipment'; // awaiting_shipment | at_relay | delivered | refused

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $refusalReason = null;

    // Horodatage du flash du colis à l'arrivée au point relais
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $scannedAt = null;

    // Horodatage de la remise effective au client (acceptation)
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deliveredAt = null;

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

    public function getRelayProfile(): ?RelayProfile
    {
        return $this->relayProfile;
    }

    public function setRelayProfile(?RelayProfile $relayProfile): static
    {
        $this->relayProfile = $relayProfile;
        return $this;
    }

    public function getPickupCode(): ?string
    {
        return $this->pickupCode;
    }

    public function setPickupCode(string $pickupCode): static
    {
        $this->pickupCode = $pickupCode;

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

    public function getRefusalReason(): ?string
    {
        return $this->refusalReason;
    }

    public function setRefusalReason(?string $refusalReason): static
    {
        $this->refusalReason = $refusalReason;

        return $this;
    }

    public function getScannedAt(): ?\DateTimeImmutable
    {
        return $this->scannedAt;
    }

    public function setScannedAt(?\DateTimeImmutable $scannedAt): static
    {
        $this->scannedAt = $scannedAt;
        return $this;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?\DateTimeImmutable $deliveredAt): static
    {
        $this->deliveredAt = $deliveredAt;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
