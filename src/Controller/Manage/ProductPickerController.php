<?php

namespace App\Controller\Manage;

use App\Entity\Category;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\BrandRepository;
use App\Repository\CategoryAttributeRepository;
use App\Repository\FuelTypeRepository;
use App\Repository\LicenseTypeRepository;
use App\Repository\MotorcycleTypeRepository;
use App\Repository\RentalPeriodRepository;
use App\Service\ProductCategoryModeResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductPickerController extends AbstractController
{
    #[Route('/produits/marques-recherche', name: 'manage_products_brands_search', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function searchProductBrands(Request $request, \App\Repository\BrandRepository $repository): Response
    {
        $term = trim((string) $request->query->get('q', ''));
        $results = mb_strlen($term) >= 2 ? $repository->searchByName($term) : [];

        return $this->json(['results' => array_map(fn (\App\Entity\Brand $b) => [
            'id' => $b->getId(),
            'label' => $b->getName(),
            'logoUrl' => $b->getLogoName() ? '/media/logos_brands/' . $b->getLogoName() : null,
        ], $results)]);
    }

    #[Route('/produits/vendeurs-recherche', name: 'manage_products_sellers_search', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function searchSellers(Request $request, \App\Repository\SellerProfileRepository $repository): Response
    {
        $term = trim((string) $request->query->get('q', ''));
        $results = mb_strlen($term) >= 2 ? $repository->searchDirectory($term, null, null) : [];

        return $this->json(['results' => array_map(fn (\App\Entity\SellerProfile $s) => [
            'id' => $s->getId(),
            'label' => $s->getUser()->getEmail(),
        ], $results)]);
    }

    #[Route('/produits/champs-categorie/{categoryId}', name: 'manage_products_category_fields', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['categoryId' => '\d+'])]
    public function categoryFields(
        int $categoryId,
        Request $request,
        EntityManagerInterface $em,
        ProductCategoryModeResolver $resolver,
        CategoryAttributeRepository $attributeRepository,
        BrandRepository $brandRepository,
        FuelTypeRepository $fuelTypeRepository,
        LicenseTypeRepository $licenseTypeRepository,
        MotorcycleTypeRepository $motorcycleTypeRepository,
        RentalPeriodRepository $rentalPeriodRepository
    ): Response {
        $category = $em->getRepository(Category::class)->find($categoryId) ?? throw $this->createNotFoundException();
        $resolved = $resolver->resolve($category);

        $productId = $request->query->get('product') ? (int) $request->query->get('product') : null;
        $product = $productId ? $em->getRepository(\App\Entity\Product::class)->find($productId) : null;

        $existingAttributeValues = [];
        if ($product) {
            foreach ($em->getRepository(\App\Entity\ProductAttributeValue::class)->findBy(['product' => $product]) as $v) {
                $existingAttributeValues[$v->getCategoryAttribute()->getId()] = $v;
            }
        }

        $context = [
            'category' => $category,
            'mode' => $resolved['mode'],
            'vehicleType' => $resolved['vehicleType'],
            'isRental' => $resolved['isRental'],
            'attributes' => $attributeRepository->findByCategory($categoryId),
            'product' => $product,
            'existingAttributeValues' => $existingAttributeValues,
        ];

        if ('vehicle_offer' === $resolved['mode'] || 'vehicle_part' === $resolved['mode']) {
            $context['brands'] = $brandRepository->findByType($resolved['vehicleType']);
        }

        if ('vehicle_part' === $resolved['mode']) {
            $context['allBrands'] = $brandRepository->findBy([], ['name' => 'ASC']);
        }

        if ('vehicle_offer' === $resolved['mode']) {
            $context['fuelTypes'] = $fuelTypeRepository->findAllActive();
            $context['licenseTypes'] = 'moto' === $resolved['vehicleType'] ? $licenseTypeRepository->findBy([], ['position' => 'ASC']) : [];
            $context['motorcycleTypes'] = 'moto' === $resolved['vehicleType'] ? $motorcycleTypeRepository->findBy([], ['position' => 'ASC']) : [];
        }

        if ('property' === $resolved['mode'] && $resolved['isRental']) {
            $context['rentalPeriods'] = $rentalPeriodRepository->findBy([], ['position' => 'ASC']);
        }

        return $this->render('manage/_partials/_product_category_fields.html.twig', $context);
    }
}