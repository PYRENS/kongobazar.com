<?php

namespace App\Controller\Manage;

use App\Entity\SidebarFillerBanner;
use App\Repository\SidebarFillerBannerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SidebarFillerBannerController extends AbstractController
{
    #[Route('/parametres/bannieres-bouche-trou-accueil', name: 'manage_sidebar_filler_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(SidebarFillerBannerRepository $repository): Response
    {
        return $this->render('manage/sidebar_filler/index.html.twig', [
            'banners' => $repository->findAllOrdered(),
            'requiredWidth' => SidebarFillerBanner::REQUIRED_WIDTH,
        ]);
    }

    #[Route('/parametres/bannieres-bouche-trou-accueil/ajouter', name: 'manage_sidebar_filler_add', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('image');
        if (!$file) {
            $this->addFlash('error', 'Une image est obligatoire.');
            return $this->redirectToRoute('manage_sidebar_filler_index');
        }

        $dimensions = @getimagesize($file->getPathname());
        $requiredWidth = SidebarFillerBanner::REQUIRED_WIDTH;
        if (!$dimensions || (int) $dimensions[0] !== $requiredWidth) {
            $this->addFlash('error', 'L\'image doit faire exactement ' . $requiredWidth . 'px de large (largeur reçue : ' . ($dimensions[0] ?? '?') . 'px). La hauteur est libre.');
            return $this->redirectToRoute('manage_sidebar_filler_index');
        }

        $banner = new SidebarFillerBanner();
        $banner->setTitle((string) $request->request->get('title', ''));
        $banner->setTargetUrl($request->request->get('target_url') ?: null);
        $banner->setOpenInNewTab((bool) $request->request->get('open_in_new_tab'));
        $banner->setImageFile($file);

        $em->persist($banner);
        $em->flush();

        $this->addFlash('success', 'Bannière ajoutée.');
        return $this->redirectToRoute('manage_sidebar_filler_index');
    }

    #[Route('/parametres/bannieres-bouche-trou-accueil/{id}/supprimer', name: 'manage_sidebar_filler_remove', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function remove(SidebarFillerBanner $banner, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($banner);
        $em->flush();

        $this->addFlash('success', 'Bannière retirée.');
        return $this->redirectToRoute('manage_sidebar_filler_index');
    }

    #[Route('/parametres/bannieres-bouche-trou-accueil/{id}/basculer', name: 'manage_sidebar_filler_toggle', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(SidebarFillerBanner $banner, EntityManagerInterface $em): Response
    {
        $banner->setActive(!$banner->isActive());
        $em->flush();

        return $this->json(['ok' => true, 'active' => $banner->isActive()]);
    }
}
