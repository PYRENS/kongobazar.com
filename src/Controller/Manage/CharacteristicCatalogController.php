<?php

namespace App\Controller\Manage;

use App\Entity\Category;
use App\Entity\CategoryAttribute;
use App\Entity\Characteristic;
use App\Repository\CategoryAttributeRepository;
use App\Repository\CharacteristicRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class CharacteristicCatalogController extends AbstractController
{
    #[Route('/caracteristiques/recherche-globale', name: 'manage_characteristic_search', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function search(Request $request, CharacteristicRepository $repository): JsonResponse
    {
        $term = trim((string) $request->query->get('q', ''));
        $results = mb_strlen($term) >= 2 ? $repository->searchByName($term) : [];

        return new JsonResponse(['results' => array_map(fn (Characteristic $c) => [
            'id' => $c->getId(),
            'label' => $c->getName() . ($c->getUnit() ? ' [' . $c->getUnit() . ']' : ''),
            'name' => $c->getName(),
            'unit' => $c->getUnit(),
            'dataType' => $c->getDataType(),
        ], $results)]);
    }

    /** Rattache (ou retrouve le rattachement existant) une caractéristique du catalogue à une catégorie, et renvoie de quoi construire le champ. */
    #[Route('/caracteristiques/lier', name: 'manage_characteristic_link', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function link(Request $request, EntityManagerInterface $em, CategoryAttributeRepository $attributeRepository): JsonResponse
    {
        $categoryId = (int) $request->request->get('category_id');
        $characteristicId = (int) $request->request->get('characteristic_id');

        $category = $em->getRepository(Category::class)->find($categoryId);
        $characteristic = $em->getRepository(Characteristic::class)->find($characteristicId);

        if (!$category || !$characteristic) {
            return new JsonResponse(['error' => 'Catégorie ou caractéristique invalide.'], 400);
        }

        $existing = null;
        foreach ($attributeRepository->findByCategory($categoryId) as $attr) {
            if ($attr->getCharacteristic()->getId() === $characteristic->getId()) {
                $existing = $attr;
                break;
            }
        }

        if (!$existing) {
            $existing = new CategoryAttribute();
            $existing->setCategory($category);
            $existing->setCharacteristic($characteristic);
            $existing->setPosition($attributeRepository->findMaxPosition($categoryId) + 1);
            $existing->setNullable(true);
            $em->persist($existing);
            $em->flush();
        }

        return new JsonResponse([
            'attributeId' => $existing->getId(),
            'name' => $characteristic->getName(),
            'unit' => $characteristic->getUnit(),
            'dataType' => $characteristic->getDataType(),
            'options' => array_map(fn ($o) => ['id' => $o->getId(), 'label' => $o->getLabel()], $characteristic->getOptions()->toArray()),
        ]);
    }

    /** Crée une caractéristique qui n'existe pas encore dans le catalogue, puis la rattache directement. */
    #[Route('/caracteristiques/nouvelle-et-lier', name: 'manage_characteristic_create_and_link', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function createAndLink(Request $request, EntityManagerInterface $em, CategoryAttributeRepository $attributeRepository): JsonResponse
    {
        $categoryId = (int) $request->request->get('category_id');
        $name = trim((string) $request->request->get('name'));
        $unit = $request->request->get('unit') ?: null;
        $dataType = $request->request->get('data_type', 'text');

        if (!in_array($dataType, ['text', 'number', 'boolean'], true)) {
            $dataType = 'text';
        }

        $category = $em->getRepository(Category::class)->find($categoryId);
        if (!$category || '' === $name) {
            return new JsonResponse(['error' => 'Catégorie ou nom invalide.'], 400);
        }

        $characteristic = new Characteristic();
        $characteristic->setName($name);
        $characteristic->setUnit($unit);
        $characteristic->setDataType($dataType);
        $em->persist($characteristic);

        $attribute = new CategoryAttribute();
        $attribute->setCategory($category);
        $attribute->setCharacteristic($characteristic);
        $attribute->setPosition($attributeRepository->findMaxPosition($categoryId) + 1);
        $attribute->setNullable(true);
        $em->persist($attribute);
        $em->flush();

        return new JsonResponse([
            'attributeId' => $attribute->getId(),
            'name' => $name,
            'unit' => $unit,
            'dataType' => $dataType,
            'options' => [],
        ]);
    }
}