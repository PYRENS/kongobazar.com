<?php

namespace App\Entity;

use App\Repository\PaysRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaysRepository::class)]
class Pays
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nameFr = null;

    #[ORM\Column(length: 100)]
    private ?string $nameEn = null;

    #[ORM\Column(length: 2)]
    private ?string $iso2 = null;

    #[ORM\Column(length: 3)]
    private ?string $iso3 = null;

    #[ORM\Column(length: 10)]
    private ?string $phoneCode = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $flag = null;

    /** @var Collection<int, Brand> */
    #[ORM\OneToMany(targetEntity: Brand::class, mappedBy: 'pays')]
    private Collection $brands;

    public function __construct()
    {
        $this->brands = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNameFr(): ?string
    {
        return $this->nameFr;
    }

    public function setNameFr(string $nameFr): static
    {
        $this->nameFr = $nameFr;

        return $this;
    }

    public function getNameEn(): ?string
    {
        return $this->nameEn;
    }

    public function setNameEn(string $nameEn): static
    {
        $this->nameEn = $nameEn;

        return $this;
    }

    public function getIso2(): ?string
    {
        return $this->iso2;
    }

    public function setIso2(string $iso2): static
    {
        $this->iso2 = $iso2;

        return $this;
    }

    public function getIso3(): ?string
    {
        return $this->iso3;
    }

    public function setIso3(string $iso3): static
    {
        $this->iso3 = $iso3;

        return $this;
    }

    public function getPhoneCode(): ?string
    {
        return $this->phoneCode;
    }

    public function setPhoneCode(string $phoneCode): static
    {
        $this->phoneCode = $phoneCode;

        return $this;
    }

    public function getFlag(): ?string
    {
        return $this->flag;
    }

    public function setFlag(?string $flag): static
    {
        $this->flag = $flag;

        return $this;
    }

    /** @return Collection<int, Brand> */
    public function getBrands(): Collection
    {
        return $this->brands;
    }

    /** Alias pratique — le nom français est celui utilisé partout dans l'admin. */
    public function getName(): ?string
    {
        return $this->nameFr;
    }

    public function __toString(): string
    {
        return ($this->flag ? $this->flag . ' ' : '') . ($this->nameFr ?? '');
    }
}