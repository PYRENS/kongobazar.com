<?php

namespace App\Entity;

use App\Repository\NegotiationThreadRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NegotiationThreadRepository::class)]
class NegotiationThread
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $buyer = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $seller = null;

    #[ORM\Column(length: 20)]
    private string $status = 'open'; // open | agreed | expired | cancelled

    /** @var Collection<int, NegotiationMessage> */
    #[ORM\OneToMany(mappedBy: 'thread', targetEntity: NegotiationMessage::class, orphanRemoval: true)]
    private Collection $messages;

    #[ORM\OneToOne(mappedBy: 'thread', targetEntity: PaymentLink::class, cascade: ['persist', 'remove'])]
    private ?PaymentLink $paymentLink = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /** @return Collection<int, NegotiationMessage> */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(NegotiationMessage $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setThread($this);
        }
        return $this;
    }

    public function getPaymentLink(): ?PaymentLink
    {
        return $this->paymentLink;
    }

    public function setPaymentLink(?PaymentLink $paymentLink): static
    {
        $this->paymentLink = $paymentLink;
        if (null !== $paymentLink && $paymentLink->getThread() !== $this) {
            $paymentLink->setThread($this);
        }
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

}
