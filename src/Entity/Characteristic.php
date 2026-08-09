<?php

namespace App\Entity;

use App\Repository\CharacteristicRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CharacteristicRepository::class)]
class Characteristic
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $unit = null;

    /** 'text' | 'number' | 'select' | 'boolean' */
    #[ORM\Column(length: 20)]
    private string $dataType = 'text';

    /** @var Collection<int, CharacteristicOption> */
    #[ORM\OneToMany(targetEntity: CharacteristicOption::class, mappedBy: 'characteristic', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $options;

    public function __construct()
    {
        $this->options = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): static
    {
        $this->unit = $unit;
        return $this;
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }

    public function setDataType(string $dataType): static
    {
        $this->dataType = $dataType;
        return $this;
    }

    /** @return Collection<int, CharacteristicOption> */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(CharacteristicOption $option): static
    {
        if (!$this->options->contains($option)) {
            $this->options->add($option);
            $option->setCharacteristic($this);
        }
        return $this;
    }

    public function removeOption(CharacteristicOption $option): static
    {
        $this->options->removeElement($option);
        return $this;
    }

    public function __toString(): string
    {
        return $this->name . ($this->unit ? ' (' . $this->unit . ')' : '');
    }
}