<?php

namespace App\Entity;

use App\Repository\IndividualSectionPriorityProductRepository;
use Doctrine\ORM\Mapping as ORM;

/** Un produit prioritaire choisi à la main pour une catégorie de la section "Particulier". */
#[ORM\Entity(repositoryClass: IndividualSectionPriorityProductRepository::class)]
#[ORM\Table(name: 'individual_section_priority_product')]
class IndividualSectionPriorityProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: IndividualSectionCategory::class, inversedBy: 'priorityProducts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?IndividualSectionCategory $sectionCategory = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\Column]
    private int $position = 0;

    public function getId(): ?int { return $this->id; }

    public function getSectionCategory(): ?IndividualSectionCategory { return $this->sectionCategory; }
    public function setSectionCategory(?IndividualSectionCategory $v): static { $this->sectionCategory = $v; return $this; }

    public function getProduct(): ?Product { return $this->product; }
    public function setProduct(?Product $product): static { $this->product = $product; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }
}
