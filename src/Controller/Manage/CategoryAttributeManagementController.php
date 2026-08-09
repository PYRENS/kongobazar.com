<?php

namespace App\Controller\Manage;

use App\Entity\Category;
use App\Entity\CategoryAttribute;
use App\Entity\CategoryAttributeOption;
use App\Repository\CategoryAttributeRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CategoryAttributeManagementController extends AbstractController
{
    #[Route('/categories/{categoryId}/caracteristiques', name: 'manage_category_attributes_index', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['categoryId' => '\d+'])]
    public function index(int $categoryId, EntityManagerInterface $em, CategoryAttributeRepository $repository): Response
    {
        $category = $em->getRepository(Category::class)->find($categoryId) ?? throw $this->createNotFoundException();

        return $this->render('manage/category_attributes/index.html.twig', [
            'category' => $category,
            'attributes' => $repository->findByCategory($categoryId),
        ]);
    }

    #[Route('/categories/{categoryId}/caracteristiques/nouveau', name: 'manage_category_attributes_new', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['categoryId' => '\d+'])]
    public function new(int $categoryId, EntityManagerInterface $em): Response
    {
        $category = $em->getRepository(Category::class)->find($categoryId) ?? throw $this->createNotFoundException();

        return $this->render('manage/category_attributes/form.html.twig', ['category' => $category, 'attribute' => null]);
    }

    #[Route('/categories/{categoryId}/caracteristiques/nouveau', name: 'manage_category_attributes_create', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['categoryId' => '\d+'])]
    public function create(int $categoryId, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $category = $em->getRepository(Category::class)->find($categoryId) ?? throw $this->createNotFoundException();

        $attribute = new CategoryAttribute();
        $attribute->setCategory($category);
        $this->hydrate($attribute, $request, $em);

        $em->persist($attribute);
        $em->flush();

        $this->addFlash('success', 'Caractéristique créée.');
        return $this->redirectToRoute('manage_category_attributes_index', ['categoryId' => $categoryId]);
    }

    #[Route('/caracteristiques/{id}/modifier', name: 'manage_category_attributes_edit', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function edit(CategoryAttribute $attribute): Response
    {
        return $this->render('manage/category_attributes/form.html.twig', [
            'category' => $attribute->getCategory(),
            'attribute' => $attribute,
        ]);
    }

    #[Route('/caracteristiques/{id}/modifier', name: 'manage_category_attributes_update', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(CategoryAttribute $attribute, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $this->hydrate($attribute, $request, $em);
        $em->flush();

        $this->addFlash('success', 'Caractéristique mise à jour.');
        return $this->redirectToRoute('manage_category_attributes_index', ['categoryId' => $attribute->getCategory()->getId()]);
    }

    #[Route('/caracteristiques/{id}/supprimer', name: 'manage_category_attributes_delete', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(CategoryAttribute $attribute, EntityManagerInterface $em): RedirectResponse
    {
        $categoryId = $attribute->getCategory()->getId();
        $em->remove($attribute);
        $em->flush();

        $this->addFlash('success', 'Caractéristique supprimée.');
        return $this->redirectToRoute('manage_category_attributes_index', ['categoryId' => $categoryId]);
    }

    #[Route('/categories/{categoryId}/caracteristiques/dupliquer', name: 'manage_category_attributes_duplicate_form', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['categoryId' => '\d+'])]
    public function duplicateForm(int $categoryId, EntityManagerInterface $em, CategoryRepository $categoryRepository): Response
    {
        $category = $em->getRepository(Category::class)->find($categoryId) ?? throw $this->createNotFoundException();

        return $this->render('manage/category_attributes/duplicate.html.twig', [
            'category' => $category,
            'allCategories' => $categoryRepository->findBy([], ['name' => 'ASC']),
            'rootCategories' => $categoryRepository->findChildrenOf(null),
        ]);
    }

    #[Route('/categories/recherche-json', name: 'manage_category_attributes_search_categories', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function searchCategories(Request $request, CategoryRepository $categoryRepository): Response
    {
        $term = trim((string) $request->query->get('q', ''));
        $results = mb_strlen($term) >= 2 ? $categoryRepository->searchByName($term) : [];

        return $this->json(['results' => array_map(fn (Category $c) => [
            'id' => $c->getId(),
            'name' => $c->getName() . ($c->getParent() ? ' (' . $c->getParent()->getName() . ')' : ''),
        ], $results)]);
    }

    #[Route('/categories/{categoryId}/caracteristiques/dupliquer/source', name: 'manage_category_attributes_duplicate_source', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['categoryId' => '\d+'])]
    public function duplicateSource(int $categoryId, Request $request, EntityManagerInterface $em, CategoryAttributeRepository $repository): Response
    {
        $category = $em->getRepository(Category::class)->find($categoryId) ?? throw $this->createNotFoundException();
        $sourceId = (int) $request->query->get('source');
        $source = $em->getRepository(Category::class)->find($sourceId);

        return $this->render('manage/category_attributes/_duplicate_checklist.html.twig', [
            'category' => $category,
            'source' => $source,
            'sourceAttributes' => $source ? $repository->findByCategory($source->getId()) : [],
        ]);
    }

    #[Route('/categories/{categoryId}/caracteristiques/dupliquer', name: 'manage_category_attributes_duplicate', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['categoryId' => '\d+'])]
    public function duplicate(int $categoryId, Request $request, EntityManagerInterface $em, CategoryAttributeRepository $repository): RedirectResponse
    {
        $category = $em->getRepository(Category::class)->find($categoryId) ?? throw $this->createNotFoundException();
        $attributeIds = $request->request->all('attribute_ids');

        $copied = 0;
        foreach ($attributeIds as $attributeId) {
            $source = $repository->find((int) $attributeId);
            if (!$source) {
                continue;
            }

            // Plus de copie de données — juste un nouveau lien vers la même caractéristique du catalogue.
            $copy = new CategoryAttribute();
            $copy->setCategory($category);
            $copy->setCharacteristic($source->getCharacteristic());
            $copy->setPosition($source->getPosition());
            $copy->setNullable($source->isNullable());
            $copy->setFilterable($source->isFilterable());
            $copy->setShowOnCard($source->isShowOnCard());
            $copy->setGroupTag($source->getGroupTag());

            $em->persist($copy);
            $copied++;
        }

        $em->flush();

        $this->addFlash('success', $copied . ' caractéristique(s) dupliquée(s).');
        return $this->redirectToRoute('manage_category_attributes_index', ['categoryId' => $categoryId]);
    }

    private function hydrate(CategoryAttribute $attribute, Request $request, EntityManagerInterface $em): void
    {
        $name = (string) $request->request->get('name');
        $dataType = (string) $request->request->get('data_type', 'text');
        $unit = $request->request->get('unit') ?: null;

        $attribute->setPosition((int) $request->request->get('position', 0));
        $attribute->setNullable((bool) $request->request->get('nullable'));
        $attribute->setFilterable((bool) $request->request->get('filterable'));
        $attribute->setShowOnCard((bool) $request->request->get('show_on_card'));
        $attribute->setGroupTag($request->request->get('group_tag') ?: null);

        $characteristic = $attribute->getCharacteristic();

        if (null === $characteristic) {
            // Nouvelle liaison : réutilise une caractéristique existante du catalogue si le nom/unité/type correspond exactement, sinon en crée une.
            $characteristicRepo = $em->getRepository(\App\Entity\Characteristic::class);
            $characteristic = $characteristicRepo->findOneByExactMatch($name, $unit, $dataType)
                ?? new \App\Entity\Characteristic();
            $attribute->setCharacteristic($characteristic);
        }

        // Édition en place — modifie la fiche du catalogue partagé (répercuté partout où elle est liée, volontairement).
        $characteristic->setName($name);
        $characteristic->setDataType($dataType);
        $characteristic->setUnit($unit);

        $em->persist($characteristic);

        // Gestion des options (uniquement pertinent pour dataType = select), désormais portées par Characteristic
        $existingOptions = [];
        foreach ($characteristic->getOptions() as $option) {
            $existingOptions[$option->getId()] = $option;
        }

        $optionIds = $request->request->all('option_id');
        $optionLabels = $request->request->all('option_label');
        $optionColors = $request->request->all('option_color');
        $keptIds = [];

        foreach ($optionLabels as $i => $label) {
            $label = trim((string) $label);
            if ('' === $label) {
                continue;
            }

            $optionId = isset($optionIds[$i]) ? (int) $optionIds[$i] : 0;
            $color = isset($optionColors[$i]) && $optionColors[$i] ? (string) $optionColors[$i] : null;

            if ($optionId && isset($existingOptions[$optionId])) {
                $existingOptions[$optionId]->setLabel($label);
                $existingOptions[$optionId]->setPosition($i);
                $existingOptions[$optionId]->setColorHex($color);
                $keptIds[] = $optionId;
            } else {
                $newOption = new \App\Entity\CharacteristicOption();
                $newOption->setLabel($label);
                $newOption->setPosition($i);
                $newOption->setColorHex($color);
                $characteristic->addOption($newOption);
            }
        }

        foreach ($existingOptions as $id => $option) {
            if (!in_array($id, $keptIds, true)) {
                $characteristic->removeOption($option);
            }
        }
    }
}