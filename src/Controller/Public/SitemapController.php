<?php

namespace App\Controller\Public;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SitemapController extends AbstractController
{
    /**
     * Sitemap unique pour l'instant (limite Google : 50 000 URL / fichier — largement suffisant
     * ici). Le jour où le catalogue grossit beaucoup, prévoir un sitemap-index avec plusieurs
     * fichiers (un pour les produits, un pour le reste) plutôt que d'agrandir celui-ci à l'infini.
     *
     * Volontairement absentes : les pages encore "en construction" (catégorie, rayon, marque,
     * blog, boutique vendeur) — les soumettre à Google alors qu'elles sont en noindex enverrait
     * un signal contradictoire. Elles rejoindront le sitemap le jour où elles seront de vraies pages.
     */
    #[Route('/sitemap.xml', name: 'public_sitemap', host: 'kongobazar.com', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAllActiveForSitemap();

        $response = $this->render('public/sitemap.xml.twig', [
            'products' => $products,
        ]);

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }
}
