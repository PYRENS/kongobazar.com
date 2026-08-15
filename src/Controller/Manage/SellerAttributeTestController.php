<?php

namespace App\Controller\Manage;

use App\Entity\CategoryAttribute;
use App\Entity\Product;
use App\Entity\ProductAttributeValue;
use App\Repository\CategoryAttributeRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class SellerAttributeTestController extends AbstractController
{
    #[Route('/test-vendeur/caracteristiques', name: 'manage_seller_attr_test_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository, CategoryAttributeRepository $attributeRepository, EntityManagerInterface $em): Response
    {
        $productId = $request->query->get('product') ? (int) $request->query->get('product') : null;
        $product = $productId ? $productRepository->find($productId) : null;

        $attributes = [];
        $existingAttributeValues = [];

        if ($product) {
            $attributes = $attributeRepository->findByCategory($product->getCategory()->getId());
            foreach ($em->getRepository(ProductAttributeValue::class)->findBy(['product' => $product]) as $v) {
                $existingAttributeValues[$v->getCategoryAttribute()->getId()] = $v;
            }
        }

        return $this->render('manage/seller_test/attributes.html.twig', [
            'product' => $product,
            'recentProducts' => $productRepository->findBy([], ['id' => 'DESC'], 20),
            'attributes' => $attributes,
            'existingAttributeValues' => $existingAttributeValues,
        ]);
    }

    #[Route('/test-vendeur/caracteristiques/{id}/enregistrer', name: 'manage_seller_attr_test_save', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function save(Product $product, Request $request, EntityManagerInterface $em, CategoryAttributeRepository $attributeRepository): RedirectResponse
    {
        $existing = [];
        foreach ($em->getRepository(ProductAttributeValue::class)->findBy(['product' => $product]) as $v) {
            $existing[$v->getCategoryAttribute()->getId()] = $v;
        }

        foreach ($request->request->all('attr') as $attrId => $raw) {
            $attrId = (int) $attrId;

            if ('' === trim((string) $raw)) {
                if (isset($existing[$attrId])) {
                    $em->remove($existing[$attrId]);
                }
                continue;
            }

            $attribute = $attributeRepository->find($attrId);
            if (!$attribute) {
                continue;
            }

            $value = $existing[$attrId] ?? new ProductAttributeValue();
            $value->setProduct($product);
            $value->setCategoryAttribute($attribute);
            $value->setTextValue(null);
            $value->setNumberValue(null);
            $value->setBooleanValue(null);
            $value->setCategoryAttributeOption(null);

            match ($attribute->getDataType()) {
                'number' => $value->setNumberValue((string) $raw),
                'boolean' => $value->setBooleanValue('1' === $raw),
                'select' => $value->setCategoryAttributeOption($em->getRepository(\App\Entity\CharacteristicOption::class)->find((int) $raw)),
                default => $value->setTextValue((string) $raw),
            };

            $em->persist($value);
        }

        $em->flush();

        $this->addFlash('success', 'Caractéristiques enregistrées pour ' . $product->getTitle() . '.');
        return $this->redirectToRoute('manage_seller_attr_test_index', ['product' => $product->getId()]);
    }
}