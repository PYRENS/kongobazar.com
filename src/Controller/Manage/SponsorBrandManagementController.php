<?php

namespace App\Controller\Manage;

use App\Entity\SponsorBrand;
use App\Repository\SponsorBrandRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SponsorBrandManagementController extends AbstractController
{
    #[Route('/parametres/marques-sponsors', name: 'manage_sponsor_brands', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(SponsorBrandRepository $repository, \App\Repository\SponsorSectionSettingRepository $sectionRepository): Response
    {
        return $this->render('manage/sponsor_brands/index.html.twig', [
            'sponsors' => $repository->findAllOrdered(),
            'sectionSetting' => $sectionRepository->getSingleton(),
        ]);
    }

    #[Route('/parametres/marques-sponsors/basculer-section', name: 'manage_sponsor_brands_toggle_section', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function toggleSection(\App\Repository\SponsorSectionSettingRepository $sectionRepository, EntityManagerInterface $em): Response
    {
        $setting = $sectionRepository->getSingleton();
        $setting->setEnabled(!$setting->isEnabled());
        $em->flush();

        return $this->json(['ok' => true, 'enabled' => $setting->isEnabled()]);
    }

    #[Route('/parametres/marques-sponsors/ajouter', name: 'manage_sponsor_brands_add', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function add(Request $request, SponsorBrandRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $name = trim((string) $request->request->get('name'));
        if ($name === '') {
            $this->addFlash('error', 'Le nom est obligatoire.');
            return $this->redirectToRoute('manage_sponsor_brands');
        }

        $sponsor = new SponsorBrand();
        $sponsor->setName($name);
        $sponsor->setUrl($request->request->get('url') ?: null);
        $sponsor->setPosition($repository->findNextPosition());

        $logoFile = $request->files->get('logo');
        if ($logoFile) {
            $sponsor->setLogoFile($logoFile);
        }

        $em->persist($sponsor);
        $em->flush();

        $this->addFlash('success', 'Marque sponsor ajoutée.');
        return $this->redirectToRoute('manage_sponsor_brands');
    }

    #[Route('/parametres/marques-sponsors/{id}/modifier', name: 'manage_sponsor_brands_update', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(SponsorBrand $sponsor, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $sponsor->setName(trim((string) $request->request->get('name')));
        $sponsor->setUrl($request->request->get('url') ?: null);

        $logoFile = $request->files->get('logo');
        if ($logoFile) {
            $sponsor->setLogoFile($logoFile);
        }

        $em->flush();

        $this->addFlash('success', 'Marque sponsor mise à jour.');
        return $this->redirectToRoute('manage_sponsor_brands');
    }

    #[Route('/parametres/marques-sponsors/{id}/supprimer', name: 'manage_sponsor_brands_remove', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function remove(SponsorBrand $sponsor, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($sponsor);
        $em->flush();

        $this->addFlash('success', 'Marque sponsor retirée.');
        return $this->redirectToRoute('manage_sponsor_brands');
    }

    #[Route('/parametres/marques-sponsors/{id}/basculer', name: 'manage_sponsor_brands_toggle', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(SponsorBrand $sponsor, EntityManagerInterface $em): Response
    {
        $sponsor->setActive(!$sponsor->isActive());
        $em->flush();

        return $this->json(['ok' => true, 'active' => $sponsor->isActive()]);
    }

    #[Route('/parametres/marques-sponsors/{id}/deplacer/{direction}', name: 'manage_sponsor_brands_move', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function move(SponsorBrand $sponsor, string $direction, SponsorBrandRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $items = $repository->findAllOrdered();
        $index = array_search($sponsor->getId(), array_map(fn ($s) => $s->getId(), $items), true);
        $swapWith = 'up' === $direction ? $index - 1 : $index + 1;

        if ($swapWith >= 0 && $swapWith < count($items)) {
            $a = $items[$index]->getPosition();
            $b = $items[$swapWith]->getPosition();
            $items[$index]->setPosition($b);
            $items[$swapWith]->setPosition($a);
            $em->flush();
        }

        return $this->redirectToRoute('manage_sponsor_brands');
    }
}
