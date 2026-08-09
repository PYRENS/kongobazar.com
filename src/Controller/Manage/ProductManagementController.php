<?php

namespace App\Controller\Manage;

use App\Entity\Brand;
use App\Entity\Category;
use App\Entity\CategoryAttribute;
use App\Entity\CategoryAttributeOption;
use App\Entity\PartCompatibility;
use App\Entity\PartListingDetails;
use App\Entity\Product;
use App\Entity\ProductAttributeValue;
use App\Entity\ProductImage;
use App\Entity\PropertyListingDetails;
use App\Entity\RentalPeriod;
use App\Entity\SellerProfile;
use App\Entity\VehicleEngine;
use App\Entity\VehicleListingDetails;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SellerProfileRepository;
use App\Service\ProductCategoryModeResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

class ProductManagementController extends AbstractController
{
    #[Route('/produits', name: 'manage_products_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(Request $request, ProductRepository $repository, CategoryRepository $categoryRepository): Response
    {
        $term = $request->query->get('q') ?: null;
        $categoryId = $request->query->get('category') ? (int) $request->query->get('category') : null;
        $status = $request->query->get('status') ?: null;
        $condition = $request->query->get('condition') ?: null;
        $sort = $request->query->get('sort', 'createdAt');
        $dir = $request->query->get('dir', 'DESC');
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 20;

        $total = $repository->countFiltered($term, $categoryId, $status, $condition);

        return $this->render('manage/products/index.html.twig', [
            'products' => $repository->findFiltered($term, $categoryId, $status, $condition, $sort, $dir, $page, $perPage),
            'categories' => $categoryRepository->findBy([], ['name' => 'ASC']),
            'searchTerm' => $term,
            'currentCategory' => $categoryId,
            'currentStatus' => $status,
            'currentCondition' => $condition,
            'currentSort' => $sort,
            'currentDir' => $dir,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'total' => $total,
        ]);
    }

    #[Route('/produits/nouveau', name: 'manage_products_new', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function new(CategoryRepository $categoryRepository): Response
    {
        return $this->render('manage/products/form.html.twig', [
            'product' => null,
            'rootCategories' => $categoryRepository->findChildrenOf(null),
        ]);
    }

    #[Route('/produits/nouveau', name: 'manage_products_create', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ProductCategoryModeResolver $resolver): RedirectResponse
    {
        if (!$this->validateRequired($request)) {
            $this->addFlash('error', 'Vendeur et Catégorie sont obligatoires — sélectionne-les dans la liste proposée avant d\'enregistrer.');
            return $this->redirectToRoute('manage_products_new');
        }

        $product = new Product();
        $this->hydrateCommon($product, $request, $em);
        $em->persist($product);
        $em->flush();

        $this->hydrateModeSpecific($product, $resolver->resolve($product->getCategory()), $request, $em);
        $this->hydrateAttributes($product, $request, $em);
        $this->hydrateImages($product, $request, $em);
        $em->flush();

        $this->addFlash('success', $product->getTitle() . ' créé.');
        return $this->redirectToRoute('manage_products_index');
    }

    #[Route('/produits/{id}/modifier', name: 'manage_products_edit', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function edit(Product $product, CategoryRepository $categoryRepository): Response
    {
        return $this->render('manage/products/form.html.twig', [
            'product' => $product,
            'rootCategories' => $categoryRepository->findChildrenOf(null),
        ]);
    }

    #[Route('/produits/{id}/modifier', name: 'manage_products_update', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(Product $product, Request $request, EntityManagerInterface $em, ProductCategoryModeResolver $resolver): RedirectResponse
    {
        if (!$this->validateRequired($request)) {
            $this->addFlash('error', 'Vendeur et Catégorie sont obligatoires — sélectionne-les dans la liste proposée avant d\'enregistrer.');
            return $this->redirectToRoute('manage_products_edit', ['id' => $product->getId()]);
        }

        $this->hydrateCommon($product, $request, $em);
        $this->hydrateModeSpecific($product, $resolver->resolve($product->getCategory()), $request, $em);
        $this->hydrateAttributes($product, $request, $em);
        $this->hydrateImages($product, $request, $em);
        $em->flush();

        $this->addFlash('success', $product->getTitle() . ' mis à jour.');
        return $this->redirectToRoute('manage_products_index');
    }

    #[Route('/produits/{id}/supprimer', name: 'manage_products_delete', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Product $product, EntityManagerInterface $em): RedirectResponse
    {
        $title = $product->getTitle();
        $em->remove($product);
        $em->flush();

        $this->addFlash('success', $title . ' supprimé.');
        return $this->redirectToRoute('manage_products_index');
    }

    private function validateRequired(Request $request): bool
    {
        return (bool) $request->request->get('seller_profile_id') && (bool) $request->request->get('category_id');
    }

    private function hydrateCommon(Product $product, Request $request, EntityManagerInterface $em): void
    {
        $title = (string) $request->request->get('title');
        $product->setTitle($title);
        $product->setDescription($request->request->get('description') ?: null);
        $product->setReference($request->request->get('reference') ?: null);

        $categoryId = (int) $request->request->get('category_id');
        $product->setCategory($em->getRepository(Category::class)->find($categoryId));

        $sellerId = (int) $request->request->get('seller_profile_id');
        $product->setSellerProfile($em->getRepository(SellerProfile::class)->find($sellerId));

        $brandId = $request->request->get('brand_id');
        $product->setBrand($brandId ? $em->getRepository(Brand::class)->find((int) $brandId) : null);

        $product->setBasePrice((string) $request->request->get('base_price'));
        $product->setCurrency($request->request->get('currency') ?: 'USD');
        $compareAt = $request->request->get('compare_at_price');
        $product->setCompareAtPrice('' !== (string) $compareAt ? (string) $compareAt : null);
        $product->setNegotiable((bool) $request->request->get('negotiable'));
        $product->setCondition($request->request->get('condition') ?: 'new');

        $quantity = $request->request->get('quantity');
        $product->setQuantity('' !== (string) $quantity ? max(0, (int) $quantity) : 1);
        $product->setStatus($request->request->get('status') ?: 'draft');

        if (null === $product->getSlug()) {
            $slugger = new AsciiSlugger();
            $product->setSlug(strtolower((string) $slugger->slug($title)) . '-' . uniqid());
        }
    }

    private function hydrateModeSpecific(Product $product, array $resolved, Request $request, EntityManagerInterface $em): void
    {
        match ($resolved['mode']) {
            'vehicle_offer' => $this->hydrateVehicleOffer($product, $resolved['vehicleType'], $request, $em),
            'vehicle_part' => $this->hydrateVehiclePart($product, $request, $em),
            'property' => $this->hydrateProperty($product, $resolved['isRental'], $request, $em),
            default => $this->hydrateGeneric($product, $request),
        };
    }

    private function hydrateGeneric(Product $product, Request $request): void
    {
        $min = $request->request->get('shipping_min_days');
        $max = $request->request->get('shipping_max_days');
        $product->setShippingMinDays('' !== (string) $min ? (int) $min : null);
        $product->setShippingMaxDays('' !== (string) $max ? (int) $max : null);
    }

    private function hydrateVehicleOffer(Product $product, string $vehicleType, Request $request, EntityManagerInterface $em): void
    {
        $details = $product->getVehicleListingDetails() ?? new VehicleListingDetails();
        $details->setProduct($product);

        $engineId = $request->request->get('vehicle_engine_id') ? (int) $request->request->get('vehicle_engine_id') : null;
        $engine = $engineId ? $em->getRepository(VehicleEngine::class)->find($engineId) : null;
        $details->setVehicleEngine($engine);

        if ($engine) {
            $brand = $engine->getVariant()
                ? $engine->getVariant()->getModel()->getBrand()
                : $engine->getModel()?->getBrand();
            $product->setBrand($brand);
        }

        $year = $request->request->get('model_year');
        $mileage = $request->request->get('mileage');
        $details->setModelYear('' !== (string) $year ? (int) $year : null);
        $details->setMileage('' !== (string) $mileage ? (int) $mileage : null);

        $details->setTrimLevel($request->request->get('trim_level') ?: null);
        $details->setConstructorVersion($request->request->get('constructor_version') ?: null);
        $details->setVehicleBodyType($request->request->get('vehicle_body_type') ?: null);
        $details->setColor($request->request->get('color') ?: null);

        $powerDin = $request->request->get('power_din');
        $details->setPowerDin('' !== (string) $powerDin ? (int) $powerDin : null);

        $firstReg = trim((string) $request->request->get('first_registration', ''));
        if (preg_match('#^(\d{1,2})/(\d{4})$#', $firstReg, $m)) {
            $details->setFirstRegistrationMonth((int) $m[1]);
            $details->setFirstRegistrationYear((int) $m[2]);
        } else {
            $details->setFirstRegistrationMonth(null);
            $details->setFirstRegistrationYear(null);
        }

        if ('moto' === $vehicleType) {
            $licenseId = $request->request->get('license_type_id');
            $motoTypeId = $request->request->get('motorcycle_type_id');
            $details->setLicenseType($licenseId ? $em->getRepository(\App\Entity\LicenseType::class)->find((int) $licenseId) : null);
            $details->setMotorcycleType($motoTypeId ? $em->getRepository(\App\Entity\MotorcycleType::class)->find((int) $motoTypeId) : null);
            $details->setSeats(null);
            $details->setSteeringSide(null);
            $details->setTransmission(null);
        } else {
            $seats = $request->request->get('seats');
            $details->setSeats('' !== (string) $seats ? (int) $seats : null);
            $details->setSteeringSide($request->request->get('steering_side') ?: null);
            $details->setTransmission($request->request->get('transmission') ?: null);
            $details->setLicenseType(null);
            $details->setMotorcycleType(null);
        }

        $em->persist($details);
        $product->setVehicleListingDetails($details);
    }

    private function hydrateVehiclePart(Product $product, Request $request, EntityManagerInterface $em): void
    {
        $details = $product->getPartListingDetails() ?? new PartListingDetails();
        $details->setProduct($product);

        $oemRaw = (string) $request->request->get('oem_codes', '');
        $details->setOemCodes($this->parseOemInput($oemRaw) ?: null);
        $details->setEan($request->request->get('ean') ?: null);
        $details->setManufacturerRef($request->request->get('manufacturer_ref') ?: null);

        foreach ($details->getCompatibilities() as $compat) {
            $details->removeCompatibility($compat);
            $em->remove($compat);
        }

        foreach ($request->request->all('compatible_brand_ids') as $brandId) {
            $brand = $em->getRepository(Brand::class)->find((int) $brandId);
            if ($brand) {
                $compat = new PartCompatibility();
                $compat->setBrand($brand);
                $details->addCompatibility($compat);
                $em->persist($compat);
            }
        }

        foreach ($details->getEngineCompatibilities() as $ec) {
            $details->removeEngineCompatibility($ec);
            $em->remove($ec);
        }

        foreach ($request->request->all('compatible_engine_ids') as $engineId) {
            $engine = $em->getRepository(\App\Entity\VehicleEngine::class)->find((int) $engineId);
            if ($engine) {
                $ec = new \App\Entity\PartEngineCompatibility();
                $ec->setVehicleEngine($engine);
                $details->addEngineCompatibility($ec);
                $em->persist($ec);
            }
        }

        $em->persist($details);
        $product->setPartListingDetails($details);
    }

    /**
     * Accepte soit une liste simple séparée par virgules ("ABC, DEF"),
     * soit le format collé "OE {code} — {MARQUE}" (une entrée par ligne), les deux mélangeables.
     * @return array<int, array{code: string, brand: ?string}>
     */
    private function parseOemInput(string $raw): array
    {
        $entries = [];
        $lines = preg_split('/\r\n|\r|\n/', trim($raw));

        foreach ($lines as $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }

            if (preg_match('/^(?:OE\s+)?(.+?)\s*[—–]\s*(.+)$/u', $line, $m)) {
                $entries[] = ['code' => trim($m[1]), 'brand' => trim($m[2])];
                continue;
            }

            foreach (explode(',', $line) as $code) {
                $code = trim((string) preg_replace('/^OE\s+/i', '', $code));
                if ('' !== $code) {
                    $entries[] = ['code' => $code, 'brand' => null];
                }
            }
        }

        return $entries;
    }

    private function hydrateProperty(Product $product, bool $isRental, Request $request, EntityManagerInterface $em): void
    {
        $details = $product->getPropertyListingDetails() ?? new PropertyListingDetails();
        $details->setProduct($product);

        $surface = $request->request->get('surface');
        $details->setSurface('' !== (string) $surface ? (string) $surface : null);

        foreach (['rooms', 'bedrooms', 'bathrooms', 'floor'] as $field) {
            $value = $request->request->get($field);
            $setter = 'set' . ucfirst($field);
            $details->$setter('' !== (string) $value ? (int) $value : null);
        }

        if ($isRental) {
            $rpId = $request->request->get('rental_period_id');
            $details->setRentalPeriod($rpId ? $em->getRepository(RentalPeriod::class)->find((int) $rpId) : null);
        } else {
            $details->setRentalPeriod(null);
        }

        $em->persist($details);
        $product->setPropertyListingDetails($details);
    }

    private function hydrateAttributes(Product $product, Request $request, EntityManagerInterface $em): void
    {
        $existing = [];
        foreach ($em->getRepository(ProductAttributeValue::class)->findBy(['product' => $product]) as $v) {
            $existing[$v->getCategoryAttribute()->getId()] = $v;
        }

        $submitted = [];
        foreach ($request->request->all('attr') as $attrId => $raw) {
            $submitted[(int) $attrId] = $raw;
        }

        foreach ($submitted as $attrId => $raw) {
            if ('' === trim((string) $raw)) {
                if (isset($existing[$attrId])) {
                    $em->remove($existing[$attrId]);
                    unset($existing[$attrId]);
                }
                continue;
            }

            $categoryAttribute = $em->getRepository(CategoryAttribute::class)->find($attrId);
            if (!$categoryAttribute) {
                continue;
            }

            $value = $existing[$attrId] ?? new ProductAttributeValue();
            $value->setProduct($product);
            $value->setCategoryAttribute($categoryAttribute);
            $value->setTextValue(null);
            $value->setNumberValue(null);
            $value->setBooleanValue(null);
            $value->setCategoryAttributeOption(null);

            match ($categoryAttribute->getDataType()) {
                'number' => $value->setNumberValue((string) $raw),
                'boolean' => $value->setBooleanValue('1' === $raw),
                'select' => $value->setCategoryAttributeOption($em->getRepository(\App\Entity\CharacteristicOption::class)->find((int) $raw)),
                default => $value->setTextValue((string) $raw),
            };

            $em->persist($value);
        }

        // supprime les valeurs dont la caractéristique n'appartient plus à cette catégorie (changement de catégorie)
        foreach ($existing as $attrId => $value) {
            if (!array_key_exists($attrId, $submitted)) {
                $em->remove($value);
            }
        }
    }

    private function hydrateImages(Product $product, Request $request, EntityManagerInterface $em): void
    {
        foreach ($request->request->all('remove_image_ids') as $id) {
            $image = $em->getRepository(ProductImage::class)->find((int) $id);
            if ($image && $image->getProduct() === $product) {
                $em->remove($image);
            }
        }
        $em->flush();

        // Prépare les nouveaux fichiers, dans l'ordre où le navigateur les envoie
        // (déjà synchronisé avec l'ordre visuel côté JS — voir syncSelectedFilesFromDom())
        $newImages = [];
        foreach ($request->files->all('images') as $file) {
            if (!$file) {
                continue;
            }
            $image = new ProductImage();
            $image->setProduct($product);
            $image->setImageFile($file);
            $em->persist($image);
            $newImages[] = $image;
        }

        // Applique l'ordre combiné (jetons "existing:ID" ou "new", dans l'ordre visuel exact)
        $orderRaw = (string) $request->request->get('image_order', '');
        $tokens = array_values(array_filter(explode(',', $orderRaw), fn ($t) => '' !== $t));

        $position = 0;
        $newIndex = 0;
        foreach ($tokens as $token) {
            if (str_starts_with($token, 'existing:')) {
                $id = (int) substr($token, 9);
                $image = $em->getRepository(ProductImage::class)->find($id);
                if ($image && $image->getProduct() === $product) {
                    $image->setPosition($position++);
                }
            } elseif ('new' === $token && isset($newImages[$newIndex])) {
                $newImages[$newIndex]->setPosition($position++);
                $newIndex++;
            }
        }

        // Filet de sécurité : tout fichier non couvert par les jetons part en fin de liste
        while ($newIndex < count($newImages)) {
            $newImages[$newIndex]->setPosition($position++);
            $newIndex++;
        }
    }
}