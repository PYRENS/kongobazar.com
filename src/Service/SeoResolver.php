<?php

namespace App\Service;

use App\Repository\SeoOverrideRepository;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Point d'entrée unique pour obtenir les métadonnées SEO d'une page publique.
 * Cherche d'abord une surcharge admin (SeoOverride) ; à défaut, retombe sur les valeurs
 * par défaut fournies par l'appelant (générées depuis les données de la fiche elle-même).
 */
class SeoResolver
{
    public function __construct(
        private readonly SeoOverrideRepository $repository,
        private readonly StorageInterface $storage,
    ) {
    }

    /**
     * @param array{metaTitle?: ?string, metaDescription?: ?string, ogImageUrl?: ?string} $defaults
     * @return array{metaTitle: ?string, metaDescription: ?string, metaKeywords: ?string, ogTitle: ?string, ogDescription: ?string, ogImageUrl: ?string, noIndex: bool, noFollow: bool}
     */
    public function resolve(string $entityType, ?int $entityId = null, ?string $pageKey = null, array $defaults = []): array
    {
        $override = $this->repository->findOverride($entityType, $entityId, $pageKey);

        $metaTitle = $override?->getMetaTitle() ?: ($defaults['metaTitle'] ?? null);
        $metaDescription = $override?->getMetaDescription() ?: ($defaults['metaDescription'] ?? null);

        $ogImageUrl = $defaults['ogImageUrl'] ?? null;
        if ($override && $override->getOgImageName()) {
            $ogImageUrl = $this->storage->resolveUri($override, 'ogImageFile');
        }

        return [
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'metaKeywords' => $override?->getMetaKeywords(),
            'ogTitle' => $override?->getOgTitle() ?: $metaTitle,
            'ogDescription' => $override?->getOgDescription() ?: $metaDescription,
            'ogImageUrl' => $ogImageUrl,
            'noIndex' => $override?->isNoIndex() ?? false,
            'noFollow' => $override?->isNoFollow() ?? false,
        ];
    }
}
