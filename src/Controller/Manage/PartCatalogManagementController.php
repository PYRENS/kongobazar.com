<?php

namespace App\Controller\Manage;

use App\Entity\Brand;
use App\Entity\Category;
use App\Entity\CategoryAttribute;
use App\Entity\CategoryAttributeOption;
use App\Entity\PartCatalogAttributeValue;
use App\Entity\PartCatalogBrandCompatibility;
use App\Entity\PartCatalogEntry;
use App\Entity\PartCatalogOemCode;
use App\Repository\CategoryAttributeRepository;
use App\Repository\CategoryRepository;
use App\Repository\PartCatalogEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PartCatalogManagementController extends AbstractController
{
    #[Route('/pieces-catalogue', name: 'manage_part_catalog_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(Request $request, PartCatalogEntryRepository $repository): Response
    {
        $status = $request->query->get('status') ?: null;
        $term = $request->query->get('q') ?: null;

        return $this->render('manage/part_catalog/index.html.twig', [
            'entries' => $repository->findFiltered($status, $term),
            'currentStatus' => $status,
            'searchTerm' => $term,
        ]);
    }

    #[Route('/pieces-catalogue/nouveau', name: 'manage_part_catalog_new', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function new(CategoryRepository $categoryRepository): Response
    {
        return $this->render('manage/part_catalog/form.html.twig', [
            'entry' => null,
            'rootCategories' => $categoryRepository->findChildrenOf(null),
        ]);
    }

    #[Route('/pieces-catalogue/nouveau', name: 'manage_part_catalog_create', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $entry = new PartCatalogEntry();
        $this->hydrateBasics($entry, $request, $em);
        $em->persist($entry);
        $em->flush();

        $this->addFlash('success', 'Fiche catalogue créée.');
        return $this->redirectToRoute('manage_part_catalog_show', ['id' => $entry->getId()]);
    }

    #[Route('/pieces-catalogue/{id}', name: 'manage_part_catalog_show', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        PartCatalogEntry $entry,
        CategoryRepository $categoryRepository,
        \App\Repository\BrandRepository $brandRepository,
        CategoryAttributeRepository $attributeRepository
    ): Response {
        $existingAttributeValues = [];
        foreach ($entry->getAttributeValues() as $v) {
            $existingAttributeValues[$v->getCategoryAttribute()->getId()] = $v;
        }

        return $this->render('manage/part_catalog/show.html.twig', [
            'entry' => $entry,
            'rootCategories' => $categoryRepository->findChildrenOf(null),
            'allBrands' => $brandRepository->findBy([], ['name' => 'ASC']),
            'vehicleBrands' => $brandRepository->findVehicleBrands(),
            'attributes' => $attributeRepository->findByCategory($entry->getCategory()->getId()),
            'existingAttributeValues' => $existingAttributeValues,
        ]);
    }

    #[Route('/pieces-catalogue/{id}/modifier', name: 'manage_part_catalog_update', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(PartCatalogEntry $entry, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $this->hydrateBasics($entry, $request, $em);
        $this->hydrateOemCodes($entry, $request, $em);
        $this->hydrateBrandCompatibilities($entry, $request, $em);
        $this->hydrateAttributes($entry, $request, $em);
        $em->flush();

        $this->addFlash('success', 'Fiche catalogue mise à jour.');
        return $this->redirectToRoute('manage_part_catalog_show', ['id' => $entry->getId()]);
    }

    #[Route('/pieces-catalogue/{id}/valider', name: 'manage_part_catalog_validate', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function validate(PartCatalogEntry $entry, EntityManagerInterface $em): RedirectResponse
    {
        $entry->setStatus('validated' === $entry->getStatus() ? 'pending_review' : 'validated');
        $em->flush();

        $this->addFlash('success', 'validated' === $entry->getStatus() ? 'Fiche validée.' : 'Fiche repassée en attente.');
        return $this->redirectToRoute('manage_part_catalog_show', ['id' => $entry->getId()]);
    }

    #[Route('/pieces-catalogue/{id}/supprimer', name: 'manage_part_catalog_delete', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(PartCatalogEntry $entry, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($entry);
        $em->flush();

        $this->addFlash('success', 'Fiche catalogue supprimée.');
        return $this->redirectToRoute('manage_part_catalog_index');
    }

    private function hydrateBasics(PartCatalogEntry $entry, Request $request, EntityManagerInterface $em): void
    {
        $entry->setName((string) $request->request->get('name'));
        $entry->setVerified((bool) $request->request->get('is_verified'));
        $entry->setComplete((bool) $request->request->get('is_complete'));
        $entry->setStatus($request->request->get('is_validated') ? 'validated' : 'pending_review');

        $categoryId = $request->request->get('category_id') ? (int) $request->request->get('category_id') : null;
        $entry->setCategory($categoryId ? $em->getRepository(Category::class)->find($categoryId) : null);

        $brandId = $request->request->get('brand_id') ? (int) $request->request->get('brand_id') : null;
        $entry->setBrand($brandId ? $em->getRepository(Brand::class)->find($brandId) : null);

        $entry->setEan($request->request->get('ean') ?: null);
        $entry->setManufacturerRef($request->request->get('manufacturer_ref') ?: null);
    }

    private function hydrateOemCodes(PartCatalogEntry $entry, Request $request, EntityManagerInterface $em): void
    {
        foreach ($entry->getOemCodes() as $existing) {
            $entry->removeOemCode($existing);
            $em->remove($existing);
        }

        $raw = (string) $request->request->get('oem_codes', '');
        $lines = preg_split('/\r\n|\r|\n/', trim($raw));

        foreach ($lines as $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }

            $brand = null;
            $code = $line;
            if (preg_match('/^(?:OE\s+)?(.+?)\s*[—–]\s*(.+)$/u', $line, $m)) {
                $code = trim($m[1]);
                $brand = $em->getRepository(Brand::class)->findOneBy(['name' => trim($m[2])]);
            } else {
                $code = trim((string) preg_replace('/^OE\s+/i', '', $line));
            }

            if ('' === $code) {
                continue;
            }

            $oem = new PartCatalogOemCode();
            $oem->setCode($code);
            $oem->setBrand($brand);
            $entry->addOemCode($oem);
            $em->persist($oem);
        }
    }

    private function hydrateBrandCompatibilities(PartCatalogEntry $entry, Request $request, EntityManagerInterface $em): void
    {
        foreach ($entry->getBrandCompatibilities() as $existing) {
            $entry->removeBrandCompatibility($existing);
            $em->remove($existing);
        }

        foreach ($request->request->all('compatible_brand_ids') as $brandId) {
            $brand = $em->getRepository(Brand::class)->find((int) $brandId);
            if ($brand) {
                $compat = new PartCatalogBrandCompatibility();
                $compat->setBrand($brand);
                $entry->addBrandCompatibility($compat);
                $em->persist($compat);
            }
        }
    }

    private function hydrateAttributes(PartCatalogEntry $entry, Request $request, EntityManagerInterface $em): void
    {
        $existing = [];
        foreach ($em->getRepository(PartCatalogAttributeValue::class)->findBy(['partCatalogEntry' => $entry]) as $v) {
            $existing[$v->getCategoryAttribute()->getId()] = $v;
        }

        foreach ($request->request->all('attr') as $attrId => $raw) {
            $attrId = (int) $attrId;

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

            $value = $existing[$attrId] ?? new PartCatalogAttributeValue();
            $value->setPartCatalogEntry($entry);
            $value->setCategoryAttribute($categoryAttribute);
            $value->setTextValue(null);
            $value->setNumberValue(null);
            $value->setBooleanValue(null);
            $value->setCategoryAttributeOption(null);

            match ($categoryAttribute->getDataType()) {
                'number' => $value->setNumberValue((string) $raw),
                'boolean' => $value->setBooleanValue('1' === $raw),
                'select' => $value->setCategoryAttributeOption($em->getRepository(CategoryAttributeOption::class)->find((int) $raw)),
                default => $value->setTextValue((string) $raw),
            };

            $em->persist($value);
        }
    }
}