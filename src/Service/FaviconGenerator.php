<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Génère toutes les tailles de favicon/icônes d'appli à partir d'UNE image source,
 * et les écrit directement dans public/ (les navigateurs/OS les demandent à des noms fixes,
 * indépendamment du pipeline AssetMapper).
 */
class FaviconGenerator
{
    /** [nomFichier => taille en pixels (carré)] */
    private const SIZES = [
        'favicon-16x16.png' => 16,
        'favicon-32x32.png' => 32,
        'apple-touch-icon.png' => 180,
        'android-chrome-192x192.png' => 192,
        'android-chrome-512x512.png' => 512,
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
    }

    /** @throws \RuntimeException si l'image source est illisible */
    public function generateFromPath(string $sourcePath, string $themeColor): void
    {
        $info = @getimagesize($sourcePath);
        if (!$info) {
            throw new \RuntimeException('Image illisible — vérifie que le fichier est bien une image (PNG, JPG, WEBP).');
        }

        $source = match ($info['mime']) {
            'image/png' => imagecreatefrompng($sourcePath),
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            default => throw new \RuntimeException('Format non supporté : ' . $info['mime'] . ' (utilise PNG, JPG ou WEBP).'),
        };

        if (!$source) {
            throw new \RuntimeException('Impossible de décoder l\'image source.');
        }

        // Recadrage carré au centre si l'image d'origine n'est pas déjà carrée.
        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);
        $squareSize = min($srcWidth, $srcHeight);
        $offsetX = (int) (($srcWidth - $squareSize) / 2);
        $offsetY = (int) (($srcHeight - $squareSize) / 2);

        $square = imagecreatetruecolor($squareSize, $squareSize);
        imagesavealpha($square, true);
        imagealphablending($square, false);
        $transparent = imagecolorallocatealpha($square, 0, 0, 0, 127);
        imagefill($square, 0, 0, $transparent);
        imagecopy($square, $source, 0, 0, $offsetX, $offsetY, $squareSize, $squareSize);
        imagedestroy($source);

        $publicDir = $this->projectDir . '/public';
        $pngBytesFor32 = null;

        foreach (self::SIZES as $filename => $size) {
            $resized = imagecreatetruecolor($size, $size);
            imagesavealpha($resized, true);
            imagealphablending($resized, false);
            $transparentResized = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefill($resized, 0, 0, $transparentResized);
            imagealphablending($resized, true);

            imagecopyresampled($resized, $square, 0, 0, 0, 0, $size, $size, $squareSize, $squareSize);
            imagepng($resized, $publicDir . '/' . $filename, 9);

            if (32 === $size) {
                ob_start();
                imagepng($resized);
                $pngBytesFor32 = ob_get_clean();
            }

            imagedestroy($resized);
        }

        imagedestroy($square);

        if ($pngBytesFor32) {
            file_put_contents($publicDir . '/favicon.ico', $this->buildIco($pngBytesFor32, 32));
        }

        file_put_contents($publicDir . '/site.webmanifest', json_encode([
            'name' => 'KongoBazar',
            'short_name' => 'KongoBazar',
            'icons' => [
                ['src' => '/android-chrome-192x192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/android-chrome-512x512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ],
            'theme_color' => $themeColor,
            'background_color' => '#ffffff',
            'display' => 'standalone',
        ], JSON_PRETTY_PRINT));
    }

    /** Construit un .ico valide au format "PNG-in-ICO" (supporté par tous les navigateurs modernes depuis ~2011). */
    private function buildIco(string $pngBytes, int $size): string
    {
        $header = pack('vvv', 0, 1, 1); // reserved=0, type=1 (icone), count=1 image
        $entry = pack(
            'CCCCvvVV',
            $size < 256 ? $size : 0, // largeur (0 = 256px)
            $size < 256 ? $size : 0, // hauteur
            0,  // palette (0 = pas de palette, vraies couleurs)
            0,  // réservé
            1,  // plans de couleur
            32, // bits par pixel
            strlen($pngBytes), // taille des données image
            22  // offset des données image (6 header + 16 entry = 22)
        );

        return $header . $entry . $pngBytes;
    }
}
