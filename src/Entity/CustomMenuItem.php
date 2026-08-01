<?php

namespace App\Entity;

use App\Repository\CustomMenuItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomMenuItemRepository::class)]
class CustomMenuItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Identifie l'emplacement d'affichage : header_main | footer_col1 | footer_col2 | footer_legal...
    #[ORM\Column(length: 50)]
    private ?string $location = null;

    #[ORM\Column(length: 100)]
    private ?string $label = null;

    // Lien externe ou statique, ex. https://... ou /a-propos
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    // Alternative : nom de route Symfony interne, ex. "public_contact"
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $internalRoute = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?self $parent = null;

    /** @var Collection<int, self> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $children;

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    #[ORM\Column(length: 20)]
    private ?string $targetSpace = null;

    #[ORM\Column]
    private ?bool $openInNewTab = false;

    #[ORM\Column]
    private ?bool $active = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getInternalRoute(): ?string
    {
        return $this->internalRoute;
    }

    public function setInternalRoute(?string $internalRoute): static
    {
        $this->internalRoute = $internalRoute;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    /** @return Collection<int, self> */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getTargetSpace(): ?string
    {
        return $this->targetSpace;
    }

    public function setTargetSpace(string $targetSpace): static
    {
        $this->targetSpace = $targetSpace;

        return $this;
    }

    public function isOpenInNewTab(): ?bool
    {
        return $this->openInNewTab;
    }

    public function setOpenInNewTab(bool $openInNewTab): static
    {
        $this->openInNewTab = $openInNewTab;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function __toString(): string
    {
        return $this->label ?? '';
    }

}
