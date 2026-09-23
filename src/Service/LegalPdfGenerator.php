<?php

namespace App\Service;

use App\Entity\LegalDocument;
use App\Entity\LegalDocumentVersion;
use Dompdf\Dompdf;
use Dompdf\Options;

/** Génère le PDF d'une version publiée d'un document légal (utilisé pour le téléchargement et l'envoi par email). */
class LegalPdfGenerator
{
    public function generate(LegalDocument $document, LegalDocumentVersion $version): string
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans'); // gère les accents français
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->buildHtml($document, $version), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function buildHtml(LegalDocument $document, LegalDocumentVersion $version): string
    {
        $articlesHtml = '';
        foreach ($version->getArticles() as $article) {
            $articlesHtml .= '<h3>' . htmlspecialchars($article->getTitle()) . '</h3>';
            $articlesHtml .= '<div>' . $article->getBody() . '</div>';
        }

        $label = htmlspecialchars($document->getLabel());
        $publishedAt = $version->getPublishedAt()?->format('d/m/Y') ?? '';

        return <<<HTML
            <html>
            <head>
                <style>
                    body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1a1a1a; }
                    h1 { font-size: 20px; margin-bottom: 4px; }
                    .subtitle { color: #666; font-size: 11px; margin-bottom: 24px; }
                    h3 { font-size: 14px; margin-top: 20px; margin-bottom: 6px; }
                    p { line-height: 1.5; }
                </style>
            </head>
            <body>
                <h1>KongoBazar — {$label}</h1>
                <p class="subtitle">Version {$version->getVersionNumber()}, en vigueur depuis le {$publishedAt}.</p>
                {$articlesHtml}
            </body>
            </html>
            HTML;
    }
}