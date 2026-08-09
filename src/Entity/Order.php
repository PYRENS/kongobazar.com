<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?User $buyer = null;

    #[ORM\ManyToOne(targetEntity: SellerProfile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?SellerProfile $sellerProfile = null; // une Order = un seul vendeur

    // UUID commun à toutes les Order payées lors d'un même passage en caisse
    #[ORM\Column(length: 36)]
    private ?string $checkoutGroup = null;

    #[ORM\Column(length: 20)]
    private string $status = 'pending'; // pending | paid | shipped | delivered | refused | cancelled | refunded

    // Montant total dans la devise choisie par l'acheteur au moment de l'achat
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $totalAmount = null;

    #[ORM\Column(length: 3)]
    private string $currency = 'USD'; // devise réellement payée par l'acheteur

    // Montant équivalent en USD, gelé, sert de référence pour la tranche de commission
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $totalAmountUsd = null;

    // Taux de change gelé au moment de la commande (null si currency = USD, pas de conversion nécessaire)
    #[ORM\Column(type: 'decimal', precision: 12, scale: 4, nullable: true)]
    private ?string $exchangeRateUsed = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $paymentMethod = null; // mpesa | orange_money | airtel_money | afrimoney | card

    #[ORM\Column(length: 20)]
    private string $escrowStatus = 'pending'; // pending | held | released | refunded

    #[ORM\ManyToOne(targetEntity: CommissionTier::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'RESTRICT')]
    private ?CommissionTier $commissionTier = null; // gelée : la tranche appliquée au moment de la commande

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $commissionAmountUsd = null; // montant de commission calculé et gelé

    /** @var Collection<int, OrderItem> */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $items;

    #[ORM\OneToOne(mappedBy: 'order', targetEntity: EscrowTransaction::class, cascade: ['persist', 'remove'])]
    private ?EscrowTransaction $escrowTransaction = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBuyer(): ?User
    {
        return $this->buyer;
    }

    public function setBuyer(?User $buyer): static
    {
        $this->buyer = $buyer;
        return $this;
    }

    public function getSellerProfile(): ?SellerProfile
    {
        return $this->sellerProfile;
    }

    public function getEscrowTransaction(): ?EscrowTransaction
    {
        return $this->escrowTransaction;
    }

    public function setEscrowTransaction(?EscrowTransaction $escrowTransaction): static
    {
        $this->escrowTransaction = $escrowTransaction;
        if (null !== $escrowTransaction && $escrowTransaction->getOrder() !== $this) {
            $escrowTransaction->setOrder($this);
        }
        return $this;
    }

    public function setSellerProfile(?SellerProfile $sellerProfile): static
    {
        $this->sellerProfile = $sellerProfile;
        return $this;
    }

    public function getCheckoutGroup(): ?string
    {
        return $this->checkoutGroup;
    }

    public function setCheckoutGroup(string $checkoutGroup): static
    {
        $this->checkoutGroup = $checkoutGroup;

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

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getTotalAmountUsd(): ?string
    {
        return $this->totalAmountUsd;
    }

    public function setTotalAmountUsd(string $totalAmountUsd): static
    {
        $this->totalAmountUsd = $totalAmountUsd;

        return $this;
    }

    public function getExchangeRateUsed(): ?string
    {
        return $this->exchangeRateUsed;
    }

    public function setExchangeRateUsed(?string $exchangeRateUsed): static
    {
        $this->exchangeRateUsed = $exchangeRateUsed;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getEscrowStatus(): ?string
    {
        return $this->escrowStatus;
    }

    public function setEscrowStatus(string $escrowStatus): static
    {
        $this->escrowStatus = $escrowStatus;

        return $this;
    }

    public function getCommissionTier(): ?CommissionTier
    {
        return $this->commissionTier;
    }

    public function setCommissionTier(?CommissionTier $commissionTier): static
    {
        $this->commissionTier = $commissionTier;
        return $this;
    }

    public function getCommissionAmountUsd(): ?string
    {
        return $this->commissionAmountUsd;
    }

    public function setCommissionAmountUsd(?string $commissionAmountUsd): static
    {
        $this->commissionAmountUsd = $commissionAmountUsd;
        return $this;
    }

    /** @return Collection<int, OrderItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }
        return $this;
    }

    public function removeItem(OrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getOrder() === $this) {
                $item->setOrder(null);
            }
        }
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
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
