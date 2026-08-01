<?php

namespace App\Controller\Public;

use App\Repository\BlogPostRepository;
use App\Repository\BrandRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StubPagesController extends AbstractController
{
    #[Route('/compte', name: 'account_index', host: 'kongobazar.com')]
    public function account(): Response
    {
        return $this->render('public/stub_generic.html.twig', [
            'title' => 'Mon compte',
            'breadcrumbs' => [
                ['label' => 'Mon compte', 'url' => null],
            ],
        ]);
    }

    #[Route('/wishlist', name: 'wishlist_index', host: 'kongobazar.com')]
    public function wishlist(): Response
    {
        return $this->render('public/stub_generic.html.twig', [
            'title' => 'Ma liste de souhaits',
            'breadcrumbs' => [
                ['label' => 'Ma liste de souhaits', 'url' => null],
            ],
        ]);
    }

    #[Route('/commande', name: 'checkout_index', host: 'kongobazar.com')]
    public function checkout(): Response
    {
        return $this->render('public/stub_generic.html.twig', [
            'title' => 'Passer commande',
            'breadcrumbs' => [
                ['label' => 'Mon panier', 'url' => $this->generateUrl('cart_index')],
                ['label' => 'Passer commande', 'url' => null],
            ],
        ]);
    }

    #[Route('/catalogue', name: 'catalog_index', host: 'kongobazar.com')]
    public function catalogIndex(): Response
    {
        return $this->render('public/stub_generic.html.twig', [
            'title' => 'Catalogue complet',
            'breadcrumbs' => [
                ['label' => 'Catalogue', 'url' => null],
            ],
        ]);
    }

    #[Route('/rayon/{slug}', name: 'catalog_rayon', host: 'kongobazar.com')]
    public function rayon(string $slug, CategoryRepository $categoryRepository): Response
    {
        $category = $categoryRepository->findOneBy(['slug' => $slug]);
        $name = $category ? $category->getName() : $slug;

        return $this->render('public/stub_generic.html.twig', [
            'title' => "Rayon : {$name}",
            'description' => 'Page à tuiles illustrées en construction.',
            'breadcrumbs' => [
                ['label' => $name, 'url' => null],
            ],
        ]);
    }

    #[Route('/categorie/{slug}', name: 'catalog_category', host: 'kongobazar.com')]
    public function category(string $slug, CategoryRepository $categoryRepository): Response
    {
        $category = $categoryRepository->findOneBy(['slug' => $slug]);
        $name = $category ? $category->getName() : $slug;

        $breadcrumbs = [];
        if ($category) {
            foreach ($category->getAncestors() as $ancestor) {
                $breadcrumbs[] = [
                    'label' => $ancestor->getName(),
                    'url' => $this->generateUrl('catalog_category', ['slug' => $ancestor->getSlug()]),
                ];
            }
        } else {
            $breadcrumbs[] = ['label' => $name, 'url' => null];
        }

        return $this->render('public/stub_generic.html.twig', [
            'title' => "Catégorie : {$name}",
            'breadcrumbs' => $breadcrumbs,
        ]);
    }


    #[Route('/marque/{slug}', name: 'catalog_brand', host: 'kongobazar.com')]
    public function brand(string $slug, BrandRepository $brandRepository): Response
    {
        $brand = $brandRepository->findOneBy(['slug' => $slug]);
        $name = $brand ? $brand->getName() : $slug;

        return $this->render('public/stub_generic.html.twig', [
            'title' => "Marque : {$name}",
            'breadcrumbs' => [
                ['label' => $name, 'url' => null],
            ],
        ]);
    }

    #[Route('/blog/{slug}', name: 'blog_show', host: 'kongobazar.com')]
    public function blogShow(string $slug, BlogPostRepository $blogPostRepository): Response
    {
        $post = $blogPostRepository->findOneBy(['slug' => $slug]);
        $title = $post ? $post->getTitle() : $slug;

        return $this->render('public/stub_generic.html.twig', [
            'title' => "Article : {$title}",
            'breadcrumbs' => [
                ['label' => $title, 'url' => null],
            ],
        ]);
    }

    #[Route('/notre-reseau/{slug}', name: 'partner_show', host: 'kongobazar.com')]
    public function partnerShow(string $slug, \App\Repository\SellerProfileRepository $sellerProfileRepository): Response
    {
        $profile = $sellerProfileRepository->findOneBySlug($slug);
        $name = $profile ? $profile->getDisplayName() : $slug;

        return $this->render('public/stub_generic.html.twig', [
            'title' => $name,
            'description' => 'Profil détaillé en construction.',
            'breadcrumbs' => [
                ['label' => 'Notre Réseau', 'url' => $this->generateUrl('network_index')],
                ['label' => $name, 'url' => null],
            ],
        ]);
    }

    #[Route('/offres/particuliers', name: 'catalog_offers_individual', host: 'kongobazar.com')]
    public function offersIndividual(): Response
    {
        return $this->render('public/stub_generic.html.twig', [
            'title' => 'Offres des Particuliers',
            'description' => 'Page en construction — filtres à venir.',
            'breadcrumbs' => [
                ['label' => 'Offres des Particuliers', 'url' => null],
            ],
        ]);
    }

    #[Route('/offres/professionnels', name: 'catalog_offers_professional', host: 'kongobazar.com')]
    public function offersProfessional(): Response
    {
        return $this->render('public/stub_generic.html.twig', [
            'title' => 'Offres des Vendeurs Pro',
            'description' => 'Page en construction — filtres à venir.',
            'breadcrumbs' => [
                ['label' => 'Offres des Vendeurs Pro', 'url' => null],
            ],
        ]);
    }

    #[Route('/offres/magasin', name: 'catalog_offers_store', host: 'kongobazar.com')]
    public function offersStore(): Response
    {
        return $this->render('public/stub_generic.html.twig', [
            'title' => 'Offres des Boutiques',
            'description' => 'Page en construction — filtres à venir.',
            'breadcrumbs' => [
                ['label' => 'Offres des Boutiques', 'url' => null],
            ],
        ]);
    }

    #[Route('/offres/kongobazar', name: 'catalog_offers_kongobazar', host: 'kongobazar.com')]
    public function offersKongobazar(): Response
    {
        return $this->render('public/stub_generic.html.twig', [
            'title' => 'Offres KongoBazar',
            'description' => 'Page en construction — filtres à venir.',
            'breadcrumbs' => [
                ['label' => 'Offres KongoBazar', 'url' => null],
            ],
        ]);
    }

}