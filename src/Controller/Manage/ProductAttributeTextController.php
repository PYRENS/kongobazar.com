<?php

namespace App\Controller\Manage;

use App\Entity\Category;
use App\Entity\CategoryAttribute;
use App\Repository\CategoryAttributeRepository;
use App\Service\ProductAttributeTextParser;
use App\Service\VehicleTextNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ProductAttributeTextController extends AbstractController
{
    #[Route('/produits/caracteristiques/analyser', name: 'manage_product_attr_analyze', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function analyze(
        Request $request,
        ProductAttributeTextParser $parser,
        CategoryAttributeRepository $attributeRepository,
        VehicleTextNormalizer $normalizer
    ): JsonResponse {
        $text = (string) $request->request->get('text', '');
        $categoryId = (int) $request->request->get('category_id');

        $parsed = $parser->parse($text);
        $existing = $attributeRepository->findByCategory($categoryId);

        $items = [];
        foreach ($parsed['items'] as $item) {
            $matched = null;
            foreach ($existing as $attr) {
                if ($normalizer->equals($attr->getName(), $item['name'])) {
                    $matched = $attr;
                    break;
                }
            }

            $items[] = [
                'name' => $item['name'],
                'unit' => $item['unit'],
                'value' => $item['value'],
                'matched' => $matched ? [
                    'found' => true,
                    'id' => $matched->getId(),
                    'dataType' => $matched->getDataType(),
                ] : ['found' => false, 'id' => null, 'dataType' => null],
            ];
        }

        return new JsonResponse(['items' => $items, 'unrecognizedLines' => $parsed['unrecognizedLines']]);
    }

    #[Route('/produits/caracteristiques/produits-recherche', name: 'manage_product_attr_source_search', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function searchSourceProducts(Request $request, \App\Repository\ProductRepository $productRepository): JsonResponse
    {
        $categoryId = (int) $request->query->get('category_id');
        $term = trim((string) $request->query->get('q', ''));
        $excludeId = $request->query->get('exclude') ? (int) $request->query->get('exclude') : null;

        $results = ($categoryId && mb_strlen($term) >= 2)
            ? $productRepository->searchByCategoryAndTerm($categoryId, $term, $excludeId)
            : [];

        return new JsonResponse(['results' => array_map(fn (\App\Entity\Product $p) => [
            'id' => $p->getId(),
            'label' => $p->getTitle(),
        ], $results)]);
    }

    #[Route('/produits/caracteristiques/produit-source', name: 'manage_product_attr_source_values', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function sourceProductValues(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $productId = (int) $request->query->get('product_id');
        $product = $em->getRepository(\App\Entity\Product::class)->find($productId);

        if (!$product) {
            return new JsonResponse(['items' => []]);
        }

        $values = $em->getRepository(\App\Entity\ProductAttributeValue::class)->findBy(['product' => $product]);

        $items = array_map(function (\App\Entity\ProductAttributeValue $v) {
            $attr = $v->getCategoryAttribute();

            return [
                'attributeId' => $attr->getId(),
                'name' => $attr->getName(),
                'unit' => $attr->getUnit(),
                'displayValue' => $v->getDisplayValue(),
                'dataType' => $attr->getDataType(),
                'rawValue' => match ($attr->getDataType()) {
                    'number' => $v->getNumberValue(),
                    'boolean' => $v->getBooleanValue() ? '1' : '0',
                    'select' => $v->getCategoryAttributeOption()?->getId(),
                    default => $v->getTextValue(),
                },
            ];
        }, $values);

        return new JsonResponse(['items' => $items]);
    }

    #[Route('/produits/caracteristiques/creer', name: 'manage_product_attr_create', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, CategoryAttributeRepository $attributeRepository): JsonResponse
    {
        $categoryId = (int) $request->request->get('category_id');
        $name = trim((string) $request->request->get('name'));
        $unit = $request->request->get('unit') ?: null;
        $sampleValue = (string) $request->request->get('sample_value', '');

        $category = $em->getRepository(Category::class)->find($categoryId);
        if (!$category || '' === $name) {
            return new JsonResponse(['error' => 'Catégorie ou nom invalide.'], 400);
        }

        $attribute = new CategoryAttribute();
        $attribute->setCategory($category);
        $attribute->setName($name);
        $attribute->setUnit($unit);
        $attribute->setDataType(preg_match('/^-?[\d\s.,]+$/u', $sampleValue) ? 'number' : 'text');
        $attribute->setPosition($attributeRepository->findMaxPosition($categoryId) + 1);
        $attribute->setNullable(true);

        $em->persist($attribute);
        $em->flush();

        return new JsonResponse([
            'id' => $attribute->getId(),
            'dataType' => $attribute->getDataType(),
        ]);
    }
}