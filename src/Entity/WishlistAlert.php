<?php

namespace App\Entity;

use App\Repository\WishlistAlertRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WishlistAlertRepository::class)]
class WishlistAlert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WishlistItem::class, inversedBy: 'alerts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?WishlistItem $wishlistItem = null;

    #[ORM\Column(length: 20)]
    private ?string $type = null; // low_stock | out_of_stock | restock | discount

    #[ORM\Column]
    private ?\DateTimeImmutable $sentAt = null;

    public function __construct()
    {
        $this->sentAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWishlistItem(): ?WishlistItem
    {
        return $this->wishlistItem;
    }

    public function setWishlistItem(?WishlistItem $wishlistItem): static
    {
        $this->wishlistItem = $wishlistItem;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }
}
