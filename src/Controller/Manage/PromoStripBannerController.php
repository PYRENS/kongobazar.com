<?php

namespace App\Controller\Manage;

use App\Entity\PromoStripBanner;
use App\Repository\PromoStripBannerRepository;
use App\Repository\PromoStripSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PromoStripBannerController extends AbstractController
{
    #[Route('/parametres/bandeau-promo-accueil', name: 'manage_promo_strip_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(PromoStripBannerRepository $repository, PromoStripSettingRepository $settingRepository): Response
    {
        return $this->render('manage/promo_strip/index.html.twig', [
            'banners' => $repository->findAllOrdered(),
            'setting' => $settingRepository->getSingleton(),
        ]);
    }

    #[Route('/parametres/bandeau-promo-accueil/basculer', name: 'manage_promo_strip_toggle_enabled', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function toggleEnabled(\App\Repository\PromoStripSettingRepository $settingRepository, EntityManagerInterface $em): Response
    {
        $setting = $settingRepository->getSingleton();
        $setting->setEnabled(!$setting->isEnabled());
        $em->flush();

        return $this->json(['ok' => true, 'enabled' => $setting->isEnabled()]);
    }

    #[Route('/parametres/bandeau-promo-accueil/nombre-affichage', name: 'manage_promo_strip_update_count', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function updateDisplayCount(Request $request, \App\Repository\PromoStripSettingRepository $settingRepository, EntityManagerInterface $em): RedirectResponse
    {
        $setting = $settingRepository->getSingleton();
        $setting->setDisplayCount((int) $request->request->get('display_count', 3));
        $em->flush();

        $this->addFlash('success', 'Nombre d\'affichage mis à jour.');
        return $this->redirectToRoute('manage_promo_strip_index');
    }

    #[Route('/parametres/bandeau-promo-accueil/ajouter', name: 'manage_promo_strip_add', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function add(Request $request, PromoStripBannerRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('image');
        if (!$file) {
            $this->addFlash('error', 'Une image est obligatoire (dimension recommandée : 434×150px).');
            return $this->redirectToRoute('manage_promo_strip_index');
        }

        $banner = new PromoStripBanner();
        $banner->setTitle((string) $request->request->get('title', ''));
        $banner->setTargetUrl($request->request->get('target_url') ?: null);
        $banner->setOpenInNewTab((bool) $request->request->get('open_in_new_tab'));
        $banner->setPosition($repository->findNextPosition());
        $banner->setImageFile($file);

        $em->persist($banner);
        $em->flush();

        $this->addFlash('success', 'Bannière ajoutée.');
        return $this->redirectToRoute('manage_promo_strip_index');
    }

    #[Route('/parametres/bandeau-promo-accueil/{id}/supprimer', name: 'manage_promo_strip_remove', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function remove(PromoStripBanner $banner, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($banner);
        $em->flush();

        $this->addFlash('success', 'Bannière retirée.');
        return $this->redirectToRoute('manage_promo_strip_index');
    }

    #[Route('/parametres/bandeau-promo-accueil/{id}/basculer', name: 'manage_promo_strip_toggle', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(PromoStripBanner $banner, EntityManagerInterface $em): Response
    {
        $banner->setActive(!$banner->isActive());
        $em->flush();

        return $this->json(['ok' => true, 'active' => $banner->isActive()]);
    }

    #[Route('/parametres/bandeau-promo-accueil/{id}/deplacer/{direction}', name: 'manage_promo_strip_move', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function move(PromoStripBanner $banner, string $direction, PromoStripBannerRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $banners = $repository->findAllOrdered();
        $index = array_search($banner->getId(), array_map(fn ($b) => $b->getId(), $banners), true);
        $swapWith = 'up' === $direction ? $index - 1 : $index + 1;

        if ($swapWith >= 0 && $swapWith < count($banners)) {
            $a = $banners[$index]->getPosition();
            $b = $banners[$swapWith]->getPosition();
            $banners[$index]->setPosition($b);
            $banners[$swapWith]->setPosition($a);
            $em->flush();
        }

        return $this->redirectToRoute('manage_promo_strip_index');
    }
}
