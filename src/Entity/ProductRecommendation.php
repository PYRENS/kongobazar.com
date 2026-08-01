<?php

namespace App\Entity;

use App\Repository\ProductRecommendationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRecommendationRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_PRODUCT_RECOMMENDATION', columns: ['product_id', 'recommended_product_id'])]
class ProductRecommendation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Le produit sur lequel la recommandation s'affiche (ex. le jouet)
    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

    // Le produit recommandé à côté (ex. les piles)
    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Product $recommendedProduct = null;

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $mutual = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function getRecommendedProduct(): ?Product
    {
        return $this->recommendedProduct;
    }

    public function setRecommendedProduct(?Product $recommendedProduct): static
    {
        $this->recommendedProduct = $recommendedProduct;
        return $this;
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

    public function isMutual(): bool
    {
        return $this->mutual;
    }

    public function setMutual(bool $mutual): static
    {
        $this->mutual = $mutual;
        return $this;
    }

}
