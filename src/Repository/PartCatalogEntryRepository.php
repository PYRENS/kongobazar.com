<?php

namespace App\Repository;

use App\Entity\PartCatalogEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PartCatalogEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PartCatalogEntry::class);
    }

    public function findByEanOrManufacturerRef(?string $ean, ?string $manufacturerRef): ?PartCatalogEntry
    {
        if ($ean) {
            $found = $this->findOneBy(['ean' => $ean]);
            if ($found) {
                return $found;
            }
        }
        if ($manufacturerRef) {
            return $this->findOneBy(['manufacturerRef' => $manufacturerRef]);
        }
        return null;
    }

    /** @return PartCatalogEntry[] */
    public function findFiltered(?string $status, ?string $term, ?array $categoryIds = null): array
    {
        $qb = $this->createQueryBuilder('e')->orderBy('e.createdAt', 'DESC');

        if ($status) {
            $qb->andWhere('e.status = :status')->setParameter('status', $status);
        }
        if ($term) {
            $qb->andWhere('e.name LIKE :term OR e.ean LIKE :term OR e.manufacturerRef LIKE :term')
                ->setParameter('term', '%' . $term . '%');
        }
        if ($categoryIds) {
            $qb->andWhere('e.category IN (:categoryIds)')->setParameter('categoryIds', $categoryIds);
        }

        return $qb->getQuery()->getResult();
    }

    /** Quantité totale en stock, tous produits rattachés confondus (somme des quantités de leurs variantes). */
    public function getTotalQuantity(int $entryId): int
    {
        $products = $this->getAttachedProducts($entryId);
        $total = 0;
        foreach ($products as $product) {
            foreach ($product->getVariants() as $variant) {
                $total += $variant->getQuantity();
            }
        }

        return $total;
    }

    /** @return array<int, array{sellerName: string, sellerId: int, productCount: int}> Vendeurs rattachés à cette fiche catalogue, via leurs produits. */
    public function getSellerUsageStats(int $entryId): array
    {
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('sp.id as sellerId, sp.displayName as sellerName, COUNT(p.id) as productCount')
            ->from(\App\Entity\Product::class, 'p')
            ->join('p.partListingDetails', 'pld')
            ->join('p.sellerProfile', 'sp')
            ->andWhere('pld.partCatalogEntry = :entryId')
            ->setParameter('entryId', $entryId)
            ->groupBy('sp.id')
            ->orderBy('productCount', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map(fn ($r) => ['sellerId' => (int) $r['sellerId'], 'sellerName' => $r['sellerName'], 'productCount' => (int) $r['productCount']], $rows);
    }

    /** @return Product[] Produits (toutes fiches vendeur confondues) directement rattachés à cette fiche catalogue. */
    public function getAttachedProducts(int $entryId): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('p')
            ->from(\App\Entity\Product::class, 'p')
            ->join('p.partListingDetails', 'pld')
            ->andWhere('pld.partCatalogEntry = :entryId')
            ->setParameter('entryId', $entryId)
            ->getQuery()
            ->getResult();
    }

    /** @return array{catalogEntries: PartCatalogEntry[], products: Product[]} Autres fiches/produits partageant au moins un code OEM identique. */
    public function findSimilarByOem(PartCatalogEntry $entry): array
    {
        $codes = array_map(fn ($oem) => $oem->getCode(), $entry->getOemCodes()->toArray());
        if (empty($codes)) {
            return ['catalogEntries' => [], 'products' => []];
        }

        $similarEntries = $this->createQueryBuilder('e')
            ->join('e.oemCodes', 'oem')
            ->andWhere('oem.code IN (:codes)')
            ->andWhere('e.id != :entryId')
            ->setParameter('codes', $codes)
            ->setParameter('entryId', $entry->getId())
            ->getQuery()
            ->getResult();

        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('p')
            ->from(\App\Entity\Product::class, 'p')
            ->join('p.partListingDetails', 'pld')
            ->setMaxResults(20);

        $orX = $qb->expr()->orX();
        foreach ($codes as $i => $code) {
            $orX->add($qb->expr()->like('pld.oemCodes', ':code' . $i));
            $qb->setParameter('code' . $i, '%"code":"' . $code . '"%');
        }
        $similarProducts = $qb->andWhere($orX)->getQuery()->getResult();

        return ['catalogEntries' => array_values(array_unique($similarEntries, SORT_REGULAR)), 'products' => $similarProducts];
    }

    /** @return array<int, array{id: int, name: string, count: int}> Rayons racines réellement utilisés par au moins une fiche catalogue. */
    public function getUsedRootCategories(): array
    {
        $rows = $this->createQueryBuilder('e')
            ->select('c.id as catId, c.name as catName')
            ->join('e.category', 'c')
            ->getQuery()
            ->getArrayResult();

        // Regroupe en PHP (pas en SQL) car il faut remonter les parents, ce que Doctrine ne fait pas nativement.
        $roots = [];
        foreach ($rows as $row) {
            $roots[] = ['id' => $row['catId'], 'name' => $row['catName']];
        }

        return $roots;
    }
}