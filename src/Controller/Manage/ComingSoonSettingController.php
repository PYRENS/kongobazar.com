<?php

namespace App\Controller\Manage;

use App\Entity\Category;
use App\Entity\ComingSoonTab;
use App\Entity\ComingSoonTabProduct;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ComingSoonSectionSettingRepository;
use App\Repository\ComingSoonTabProductRepository;
use App\Repository\ComingSoonTabRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ComingSoonSettingController extends AbstractController
{
    #[Route('/parametres/prochainement-accueil', name: 'manage_coming_soon_setting', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(ComingSoonTabRepository $repository, ComingSoonSectionSettingRepository $sectionRepository): Response
    {
        return $this->render('manage/coming_soon_setting/index.html.twig', [
            'tabs' => $repository->findAllOrdered(),
            'sectionSetting' => $sectionRepository->getSingleton(),
        ]);
    }

    #[Route('/parametres/prochainement-accueil/basculer', name: 'manage_coming_soon_toggle_enabled', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function toggleEnabled(ComingSoonSectionSettingRepository $repository, EntityManagerInterface $em): Response
    {
        $setting = $repository->getSingleton();
        $setting->setEnabled(!$setting->isEnabled());
        $em->flush();

        return $this->json(['ok' => true, 'enabled' => $setting->isEnabled()]);
    }

    #[Route('/parametres/prochainement-accueil/titre', name: 'manage_coming_soon_update_title', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function updateTitle(Request $request, ComingSoonSectionSettingRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $setting = $repository->getSingleton();
        $title = trim((string) $request->request->get('title'));
        if ($title !== '') {
            $setting->setTitle($title);
            $em->flush();
            $this->addFlash('success', 'Titre mis à jour.');
        }

        return $this->redirectToRoute('manage_coming_soon_setting');
    }

    #[Route('/parametres/prochainement-accueil/onglet/ajouter', name: 'manage_coming_soon_add_tab', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function addTab(Request $request, ComingSoonTabRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $categoryId = (int) $request->request->get('category_id');
        $category = $em->getRepository(Category::class)->find($categoryId);

        if (!$category) {
            $this->addFlash('error', 'Catégorie introuvable.');
            return $this->redirectToRoute('manage_coming_soon_setting');
        }

        $existing = $em->getRepository(ComingSoonTab::class)->findOneBy(['category' => $category]);
        if ($existing) {
            $this->addFlash('error', 'Cette catégorie a déjà un onglet configuré.');
            return $this->redirectToRoute('manage_coming_soon_setting');
        }

        $tab = new ComingSoonTab();
        $tab->setCategory($category);
        $tab->setPosition($repository->findNextPosition());
        $em->persist($tab);
        $em->flush();

        $this->addFlash('success', 'Onglet ajouté.');
        return $this->redirectToRoute('manage_coming_soon_setting');
    }

    #[Route('/parametres/prochainement-accueil/onglet/{id}/supprimer', name: 'manage_coming_soon_remove_tab', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function removeTab(ComingSoonTab $tab, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($tab);
        $em->flush();

        $this->addFlash('success', 'Onglet retiré.');
        return $this->redirectToRoute('manage_coming_soon_setting');
    }

    #[Route('/parametres/prochainement-accueil/onglet/{id}/deplacer/{direction}', name: 'manage_coming_soon_move_tab', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function moveTab(ComingSoonTab $tab, string $direction, ComingSoonTabRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $tabs = $repository->findAllOrdered();
        $index = array_search($tab->getId(), array_map(fn ($t) => $t->getId(), $tabs), true);
        $swapWith = 'up' === $direction ? $index - 1 : $index + 1;

        if ($swapWith >= 0 && $swapWith < count($tabs)) {
            $a = $tabs[$index]->getPosition();
            $b = $tabs[$swapWith]->getPosition();
            $tabs[$index]->setPosition($b);
            $tabs[$swapWith]->setPosition($a);
            $em->flush();
        }

        return $this->redirectToRoute('manage_coming_soon_setting');
    }

    #[Route('/parametres/prochainement-accueil/onglet/{id}/produit/ajouter', name: 'manage_coming_soon_add_product', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addProduct(ComingSoonTab $tab, Request $request, ComingSoonTabProductRepository $tabProductRepository, EntityManagerInterface $em): Response
    {
        $productId = (int) $request->request->get('product_id');
        $product = $em->getRepository(Product::class)->find($productId);

        // Garde-fou : seuls les produits au statut "futur" peuvent être ajoutés ici — jamais
        // un produit désactivé pour une autre raison (non-conformité, etc.).
        if (!$product || 'futur' !== $product->getStatus()) {
            return $this->json(['ok' => false, 'error' => 'Produit introuvable ou non au statut "Futur".']);
        }

        foreach ($tab->getProducts() as $existing) {
            if ($existing->getProduct()->getId() === $product->getId()) {
                return $this->json(['ok' => false, 'error' => 'Ce produit est déjà dans cet onglet.']);
            }
        }

        $item = new ComingSoonTabProduct();
        $item->setTab($tab);
        $item->setProduct($product);
        $item->setPosition($tabProductRepository->findNextPosition($tab));
        $em->persist($item);
        $em->flush();

        return $this->json(['ok' => true, 'itemId' => $item->getId(), 'productTitle' => $product->getTitle()]);
    }

    #[Route('/parametres/prochainement-accueil/produit/{id}/supprimer', name: 'manage_coming_soon_remove_product', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function removeProduct(ComingSoonTabProduct $item, EntityManagerInterface $em): Response
    {
        $em->remove($item);
        $em->flush();

        return $this->json(['ok' => true]);
    }

    /** Recherche de produits par nom, strictement isolée au statut "futur" (jamais draft/suspended/active). */
    #[Route('/parametres/prochainement-accueil/rechercher-produits', name: 'manage_coming_soon_search_products', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function searchProducts(Request $request, ProductRepository $productRepository): Response
    {
        $term = trim((string) $request->query->get('q', ''));

        $qb = $productRepository->createQueryBuilder('p')
            ->andWhere('p.status = :status')->setParameter('status', 'futur')
            ->setMaxResults(20);

        if ($term) {
            $qb->andWhere('p.title LIKE :term')->setParameter('term', '%' . $term . '%');
        }

        $results = $qb->getQuery()->getResult();

        return $this->json(['results' => array_map(fn (Product $p) => [
            'id' => $p->getId(),
            'name' => $p->getTitle(),
        ], $results)]);
    }

    /** Cascade générique — enfants d'une catégorie, n'importe quel niveau. */
    #[Route('/parametres/prochainement-accueil/categories-enfants', name: 'manage_coming_soon_children_cascade', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function childrenCategories(Request $request, CategoryRepository $categoryRepository): Response
    {
        $parentId = $request->query->get('parent_id') ? (int) $request->query->get('parent_id') : null;
        $children = $categoryRepository->findChildrenOf($parentId);

        return $this->json(['results' => array_map(fn (Category $c) => [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'hasChildren' => count($categoryRepository->findChildrenOf($c->getId())) > 0,
        ], $children)]);
    }
}
