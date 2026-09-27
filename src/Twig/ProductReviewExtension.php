<?php

namespace App\Twig;

use App\Repository\ProductReviewRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/** Fonction Twig product_review_stats(product) : { average, count }, avec mémoire par requête. */
class ProductReviewExtension extends AbstractExtension
{
    private array $cache = [];

    public function __construct(private readonly ProductReviewRepository $productReviewRepository)
    {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('product_review_stats', [$this, 'stats'])];
    }

    public function stats($product): array
    {
        $id = $product->getId();
        if (!isset($this->cache[$id])) {
            $this->cache[$id] = $this->productReviewRepository->getStatsForProduct($id);
        }

        return $this->cache[$id];
    }
}