<?php

namespace App\Controller\Manage;

use App\Entity\PartCatalogEntry;
use App\Entity\PartCatalogImage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class PartCatalogGalleryController extends AbstractController
{
    #[Route('/pieces-catalogue/{id}/galerie', name: 'manage_part_catalog_gallery_update', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(PartCatalogEntry $entry, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        foreach ($request->request->all('remove_image_ids') as $id) {
            $image = $em->getRepository(PartCatalogImage::class)->find((int) $id);
            if ($image && $image->getPartCatalogEntry() === $entry) {
                $entry->removeImage($image);
                $em->remove($image);
            }
        }
        $em->flush();

        $position = count($entry->getImages());
        foreach ($request->files->all('images') as $file) {
            if (!$file) {
                continue;
            }
            $image = new PartCatalogImage();
            $image->setPartCatalogEntry($entry);
            $image->setImageFile($file);
            $image->setPosition($position++);
            $em->persist($image);
        }
        $em->flush();

        $orderRaw = (string) $request->request->get('image_order', '');
        $orderedIds = array_values(array_filter(array_map('intval', explode(',', $orderRaw))));
        $rank = 0;
        foreach ($orderedIds as $id) {
            $image = $em->getRepository(PartCatalogImage::class)->find($id);
            if ($image && $image->getPartCatalogEntry() === $entry) {
                $image->setPosition($rank++);
            }
        }
        $em->flush();

        $this->addFlash('success', 'Galerie mise à jour.');
        return $this->redirectToRoute('manage_part_catalog_show', ['id' => $entry->getId()]);
    }
}