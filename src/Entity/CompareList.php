<?php

namespace App\Entity;

use App\Repository\CompareListRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompareListRepository::class)]
class CompareList
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, unique: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $sessionToken = null;

    /** @var Collection<int, CompareItem> */
    #[ORM\OneToMany(mappedBy: 'compareList', targetEntity: CompareItem::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $items;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getSessionToken(): ?string
    {
        return $this->sessionToken;
    }

    public function setSessionToken(?string $sessionToken): static
    {
        $this->sessionToken = $sessionToken;

        return $this;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(CompareItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setCompareList($this);
        }
        return $this;
    }

    public function removeItem(CompareItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getCompareList() === $this) {
                $item->setCompareList(null);
            }
        }
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
