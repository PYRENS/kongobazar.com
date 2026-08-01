<?php

namespace App\Entity;

use App\Repository\ExchangeRateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExchangeRateRepository::class)]
class ExchangeRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Valeur d'1 USD exprimée en CDF (ex. 2800.0000)
    #[ORM\Column(type: 'decimal', precision: 12, scale: 4)]
    private ?string $rateUsdToCdf = null;

    // manual | api
    #[ORM\Column(length: 10)]
    private string $source = 'manual';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $setBy = null; // admin ayant validé/saisi ce taux ; null si 100% automatique

    #[ORM\Column]
    private ?\DateTimeImmutable $effectiveAt = null;

    public function __construct()
    {
        $this->effectiveAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRateUsdToCdf(): ?string
    {
        return $this->rateUsdToCdf;
    }

    public function setRateUsdToCdf(string $rate): static
    {
        $this->rateUsdToCdf = $rate;
        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;
        return $this;
    }

    public function getSetBy(): ?User
    {
        return $this->setBy;
    }

    public function setSetBy(?User $setBy): static
    {
        $this->setBy = $setBy;
        return $this;
    }

    public function getEffectiveAt(): ?\DateTimeImmutable
    {
        return $this->effectiveAt;
    }

    public function setEffectiveAt(\DateTimeImmutable $effectiveAt): static
    {
        $this->effectiveAt = $effectiveAt;
        return $this;
    }
}