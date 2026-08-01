<?php

namespace App\Entity;

use App\Repository\NegotiationMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NegotiationMessageRepository::class)]
class NegotiationMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: NegotiationThread::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?NegotiationThread $thread = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $sender = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    // Renseigné uniquement si le message porte une offre de prix chiffrée
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $offerAmount = null;

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

    public function getThread(): ?NegotiationThread
    {
        return $this->thread;
    }

    public function setThread(?NegotiationThread $thread): static
    {
        $this->thread = $thread;
        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;
        return $this;
    }    

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getOfferAmount(): ?string
    {
        return $this->offerAmount;
    }

    public function setOfferAmount(?string $offerAmount): static
    {
        $this->offerAmount = $offerAmount;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

}
