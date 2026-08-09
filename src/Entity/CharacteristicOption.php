<?php

namespace App\Entity;

use App\Repository\CharacteristicOptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CharacteristicOptionRepository::class)]
class CharacteristicOption
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Characteristic::class, inversedBy: 'options')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Characteristic $characteristic = null;

    #[ORM\Column(length: 100)]
    private ?string $label = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $colorHex = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCharacteristic(): ?Characteristic
    {
        return $this->characteristic;
    }

    public function setCharacteristic(?Characteristic $characteristic): static
    {
        $this->characteristic = $characteristic;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function getColorHex(): ?string
    {
        return $this->colorHex;
    }

    public function setColorHex(?string $colorHex): static
    {
        $this->colorHex = $colorHex;
        return $this;
    }

    public function __toString(): string
    {
        return $this->label ?? '';
    }
}