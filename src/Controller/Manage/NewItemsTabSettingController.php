<?php

namespace App\Controller\Manage;

use App\Entity\Category;
use App\Entity\NewItemsTab;
use App\Entity\NewItemsTabTargetedProduct;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\NewItemsSectionSettingRepository;
use App\Repository\NewItemsTabRepository;
use App\Repository\NewItemsTabTargetedProductRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NewItemsTabSettingController extends AbstractController
{
    public const MODES = [
        'auto' => 'Automatique (nouveaux articles, hors Particuliers)',
        'targeted' => 'Ciblé (choix manuel)',
    ];

    public const BIG_CARD_MODES = [
        'random' => 'Aléatoire parmi la sélection',
        'manual' => 'Choisi manuellement',
    ];

    #[Route('/parametres/nouveautes-accueil', name: 'manage_new_items_setting', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(NewItemsTabRepository $repository, NewItemsSectionSettingRepository $sectionRepository): Response
    {
        return $this->render('manage/new_items_setting/index.html.twig', [
            'tabs' => $repository->findAllOrdered(),
            'sectionSetting' => $sectionRepository->getSingleton(),
            'modes' => self::MODES,
            'bigCardModes' => self::BIG_CARD_MODES,
        ]);
    }

    #[Route('/parametres/nouveautes-accueil/basculer', name: 'manage_new_items_toggle_enabled', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function toggleEnabled(NewItemsSectionSettingRepository $repository, EntityManagerInterface $em): Response
    {
        $setting = $repository->getSingleton();
        $setting->setEnabled(!$setting->isEnabled());
        $em->flush();

        return $this->json(['ok' => true, 'enabled' => $setting->isEnabled()]);
    }

    #[Route('/parametres/nouveautes-accueil/onglet/ajouter', name: 'manage_new_items_add_tab', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function addTab(Request $request, NewItemsTabRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $categoryId = (int) $request->request->get('category_id');
        $category = $em->getRepository(Category::class)->find($categoryId);

        if (!$category) {
            $this->addFlash('error', 'Catégorie introuvable.');
            return $this->redirectToRoute('manage_new_items_setting');
        }

        $existing = $em->getRepository(NewItemsTab::class)->findOneBy(['category' => $category]);
        if ($existing) {
            $this->addFlash('error', 'Cette catégorie a déjà un onglet configuré.');
            return $this->redirectToRoute('manage_new_items_setting');
        }

        $tab = new NewItemsTab();
        $tab->setCategory($category);
        $tab->setPosition($repository->findNextPosition());
        $em->persist($tab);
        $em->flush();

        $this->addFlash('success', 'Onglet ajouté.');
        return $this->redirectToRoute('manage_new_items_setting');
    }

    #[Route('/parametres/nouveautes-accueil/onglet/{id}/supprimer', name: 'manage_new_items_remove_tab', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function removeTab(NewItemsTab $tab, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($tab);
        $em->flush();

        $this->addFlash('success', 'Onglet retiré.');
        return $this->redirectToRoute('manage_new_items_setting');
    }

    #[Route('/parametres/nouveautes-accueil/onglet/{id}/deplacer/{direction}', name: 'manage_new_items_move_tab', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function moveTab(NewItemsTab $tab, string $direction, NewItemsTabRepository $repository, EntityManagerInterface $em): RedirectResponse
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

        return $this->redirectToRoute('manage_new_items_setting');
    }

    #[Route('/parametres/nouveautes-accueil/onglet/{id}/enregistrer', name: 'manage_new_items_update_tab', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateTab(NewItemsTab $tab, Request $request, EntityManagerInterface $em): Response
    {
        $tab->setMode($request->request->get('mode', 'auto'));
        $tab->setProductCount(max(2, (int) $request->request->get('product_count', 7)));

        $em->flush();

        return $this->json(['ok' => true]);
    }

    #[Route('/parametres/nouveautes-accueil/produit/{id}/basculer-grande-carte', name: 'manage_new_items_toggle_big_card', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleBigCard(NewItemsTabTargetedProduct $item, EntityManagerInterface $em): Response
    {
        $newState = !$item->isBigCard();

        // Un seul produit coché à la fois par onglet — on décoche tous les autres avant.
        foreach ($item->getTab()->getTargetedProducts() as $sibling) {
            $sibling->setIsBigCard(false);
        }
        $item->setIsBigCard($newState);

        $em->flush();

        return $this->json(['ok' => true, 'isBigCard' => $item->isBigCard()]);
    }

    #[Route('/parametres/nouveautes-accueil/onglet/{id}/produit/ajouter', name: 'manage_new_items_add_product', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addTargetedProduct(NewItemsTab $tab, Request $request, NewItemsTabTargetedProductRepository $targetedRepository, EntityManagerInterface $em): Response
    {
        $productId = (int) $request->request->get('product_id');
        $product = $em->getRepository(Product::class)->find($productId);

        if (!$product) {
            return $this->json(['ok' => false, 'error' => 'Produit introuvable.'], 404);
        }

        foreach ($tab->getTargetedProducts() as $item) {
            if ($item->getProduct()->getId() === $product->getId()) {
                return $this->json(['ok' => false, 'error' => 'Ce produit est déjà dans cet onglet.']);
            }
        }

        $item = new NewItemsTabTargetedProduct();
        $item->setTab($tab);
        $item->setProduct($product);
        $item->setPosition($targetedRepository->findNextPosition($tab));
        $em->persist($item);
        $em->flush();

        return $this->json([
            'ok' => true,
            'itemId' => $item->getId(),
            'productTitle' => $product->getTitle(),
        ]);
    }

    #[Route('/parametres/nouveautes-accueil/produit/{id}/supprimer', name: 'manage_new_items_remove_product', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function removeTargetedProduct(NewItemsTabTargetedProduct $item, EntityManagerInterface $em): Response
    {
        $em->remove($item);
        $em->flush();

        return $this->json(['ok' => true]);
    }

    #[Route('/parametres/nouveautes-accueil/produit/{id}/deplacer/{direction}', name: 'manage_new_items_move_product', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function moveTargetedProduct(NewItemsTabTargetedProduct $item, string $direction, NewItemsTabTargetedProductRepository $targetedRepository, EntityManagerInterface $em): RedirectResponse
    {
        $items = $targetedRepository->findByTabOrdered($item->getTab());
        $index = array_search($item->getId(), array_map(fn ($i) => $i->getId(), $items), true);
        $swapWith = 'up' === $direction ? $index - 1 : $index + 1;

        if ($swapWith >= 0 && $swapWith < count($items)) {
            $a = $items[$index]->getPosition();
            $b = $items[$swapWith]->getPosition();
            $items[$index]->setPosition($b);
            $items[$swapWith]->setPosition($a);
            $em->flush();
        }

        return $this->redirectToRoute('manage_new_items_setting');
    }

    #[Route('/parametres/nouveautes-accueil/rechercher-produits', name: 'manage_new_items_search_products', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function searchProducts(Request $request, ProductRepository $productRepository): Response
    {
        $term = trim((string) $request->query->get('q', ''));

        $qb = $productRepository->createQueryBuilder('p')
            ->andWhere('p.status = :status')->setParameter('status', 'active')
            ->setMaxResults(20);

        if ($term) {
            $qb->andWhere('p.title LIKE :term')->setParameter('term', '%' . $term . '%');
        }

        $results = $qb->getQuery()->getResult();

        return $this->json(['results' => array_map(fn (Product $p) => [
            'id' => $p->getId(),
            'name' => $p->getTitle() . ' (' . $p->getKongobazarReference() . ')',
        ], $results)]);
    }

    /** Cascade générique — enfants d'une catégorie, n'importe quel niveau. */
    #[Route('/parametres/nouveautes-accueil/categories-enfants', name: 'manage_new_items_children_cascade', host: 'manage.kongobazar.com', methods: ['GET'])]
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
