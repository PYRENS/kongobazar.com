<?php

namespace App\Entity;

use App\Repository\FooterSettingRepository;
use Doctrine\ORM\Mapping as ORM;

/** Singleton — réglages généraux du footer (texte de présentation, téléphone, adresse). */
#[ORM\Entity(repositoryClass: FooterSettingRepository::class)]
class FooterSetting
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $companyDescription = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $phoneAvailability = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    public function getId(): int { return $this->id; }

    public function getCompanyDescription(): ?string { return $this->companyDescription; }
    public function setCompanyDescription(?string $v): static { $this->companyDescription = $v; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $v): static { $this->phone = $v; return $this; }

    public function getPhoneAvailability(): ?string { return $this->phoneAvailability; }
    public function setPhoneAvailability(?string $v): static { $this->phoneAvailability = $v; return $this; }

    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $v): static { $this->address = $v; return $this; }
}
