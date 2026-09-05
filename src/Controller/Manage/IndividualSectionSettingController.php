<?php

namespace App\Controller\Manage;

use App\Entity\Category;
use App\Entity\IndividualSectionCategory;
use App\Entity\IndividualSectionPriorityProduct;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\IndividualSectionCategoryRepository;
use App\Repository\IndividualSectionPriorityProductRepository;
use App\Repository\IndividualSectionSettingRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndividualSectionSettingController extends AbstractController
{
    #[Route('/parametres/particulier-accueil', name: 'manage_individual_section_setting', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(IndividualSectionCategoryRepository $repository, IndividualSectionSettingRepository $sectionRepository): Response
    {
        return $this->render('manage/individual_section_setting/index.html.twig', [
            'categories' => $repository->findAllOrdered(),
            'sectionSetting' => $sectionRepository->getSingleton(),
        ]);
    }

    #[Route('/parametres/particulier-accueil/basculer', name: 'manage_individual_section_toggle_enabled', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function toggleEnabled(IndividualSectionSettingRepository $repository, EntityManagerInterface $em): Response
    {
        $setting = $repository->getSingleton();
        $setting->setEnabled(!$setting->isEnabled());
        $em->flush();

        return $this->json(['ok' => true, 'enabled' => $setting->isEnabled()]);
    }

    #[Route('/parametres/particulier-accueil/categorie/ajouter', name: 'manage_individual_section_add_category', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function addCategory(Request $request, IndividualSectionCategoryRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $categoryId = (int) $request->request->get('category_id');
        $category = $em->getRepository(Category::class)->find($categoryId);

        if (!$category) {
            $this->addFlash('error', 'Catégorie introuvable.');
            return $this->redirectToRoute('manage_individual_section_setting');
        }

        $existing = $em->getRepository(IndividualSectionCategory::class)->findOneBy(['category' => $category]);
        if ($existing) {
            $this->addFlash('error', 'Cette catégorie est déjà configurée.');
            return $this->redirectToRoute('manage_individual_section_setting');
        }

        $sectionCategory = new IndividualSectionCategory();
        $sectionCategory->setCategory($category);
        $sectionCategory->setPosition($repository->findNextPosition());
        $em->persist($sectionCategory);
        $em->flush();

        $this->addFlash('success', 'Catégorie ajoutée.');
        return $this->redirectToRoute('manage_individual_section_setting');
    }

    #[Route('/parametres/particulier-accueil/categorie/{id}/supprimer', name: 'manage_individual_section_remove_category', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function removeCategory(IndividualSectionCategory $sectionCategory, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($sectionCategory);
        $em->flush();

        $this->addFlash('success', 'Catégorie retirée.');
        return $this->redirectToRoute('manage_individual_section_setting');
    }

    #[Route('/parametres/particulier-accueil/categorie/{id}/deplacer/{direction}', name: 'manage_individual_section_move_category', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function moveCategory(IndividualSectionCategory $sectionCategory, string $direction, IndividualSectionCategoryRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $categories = $repository->findAllOrdered();
        $index = array_search($sectionCategory->getId(), array_map(fn ($c) => $c->getId(), $categories), true);
        $swapWith = 'up' === $direction ? $index - 1 : $index + 1;

        if ($swapWith >= 0 && $swapWith < count($categories)) {
            $a = $categories[$index]->getPosition();
            $b = $categories[$swapWith]->getPosition();
            $categories[$index]->setPosition($b);
            $categories[$swapWith]->setPosition($a);
            $em->flush();
        }

        return $this->redirectToRoute('manage_individual_section_setting');
    }

    #[Route('/parametres/particulier-accueil/categorie/{id}/nombre-cartes', name: 'manage_individual_section_update_count', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateCardCount(IndividualSectionCategory $sectionCategory, Request $request, EntityManagerInterface $em): Response
    {
        $sectionCategory->setCardCount(max(1, (int) $request->request->get('card_count', 8)));
        $em->flush();

        return $this->json(['ok' => true]);
    }

    #[Route('/parametres/particulier-accueil/categorie/{id}/produit-prioritaire/ajouter', name: 'manage_individual_section_add_priority_product', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addPriorityProduct(IndividualSectionCategory $sectionCategory, Request $request, IndividualSectionPriorityProductRepository $priorityRepository, EntityManagerInterface $em): Response
    {
        $productId = (int) $request->request->get('product_id');
        $product = $em->getRepository(Product::class)->find($productId);

        // Garde-fou : uniquement des produits actifs de vendeurs "Particulier".
        if (!$product || 'active' !== $product->getStatus() || !($product->getSellerProfile() instanceof \App\Entity\IndividualProfile)) {
            return $this->json(['ok' => false, 'error' => 'Produit introuvable ou non vendu par un particulier.']);
        }

        foreach ($sectionCategory->getPriorityProducts() as $item) {
            if ($item->getProduct()->getId() === $product->getId()) {
                return $this->json(['ok' => false, 'error' => 'Ce produit est déjà prioritaire pour cette catégorie.']);
            }
        }

        $item = new IndividualSectionPriorityProduct();
        $item->setSectionCategory($sectionCategory);
        $item->setProduct($product);
        $item->setPosition($priorityRepository->findNextPosition($sectionCategory));
        $em->persist($item);
        $em->flush();

        return $this->json(['ok' => true, 'itemId' => $item->getId(), 'productTitle' => $product->getTitle()]);
    }

    #[Route('/parametres/particulier-accueil/produit-prioritaire/{id}/supprimer', name: 'manage_individual_section_remove_priority_product', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function removePriorityProduct(IndividualSectionPriorityProduct $item, EntityManagerInterface $em): Response
    {
        $em->remove($item);
        $em->flush();

        return $this->json(['ok' => true]);
    }

    /** Recherche isolée aux produits actifs vendus par des particuliers. */
    #[Route('/parametres/particulier-accueil/rechercher-produits', name: 'manage_individual_section_search_products', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function searchProducts(Request $request, ProductRepository $productRepository): Response
    {
        $term = trim((string) $request->query->get('q', ''));

        $qb = $productRepository->createQueryBuilder('p')
            ->join('p.sellerProfile', 's')
            ->andWhere('p.status = :status')->setParameter('status', 'active')
            ->andWhere('s INSTANCE OF App\Entity\IndividualProfile')
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
    #[Route('/parametres/particulier-accueil/categories-enfants', name: 'manage_individual_section_children_cascade', host: 'manage.kongobazar.com', methods: ['GET'])]
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
