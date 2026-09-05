<?php

namespace App\Controller\Manage;

use App\Entity\Partner;
use App\Repository\PartnerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PartnerManagementController extends AbstractController
{
    #[Route('/parametres/partenaires', name: 'manage_partners', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(PartnerRepository $repository, \App\Repository\PartnerSectionSettingRepository $sectionRepository): Response
    {
        return $this->render('manage/partners/index.html.twig', [
            'partners' => $repository->findAllOrdered(),
            'sectionSetting' => $sectionRepository->getSingleton(),
        ]);
    }

    #[Route('/parametres/partenaires/basculer-section', name: 'manage_partners_toggle_section', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function toggleSection(\App\Repository\PartnerSectionSettingRepository $sectionRepository, EntityManagerInterface $em): Response
    {
        $setting = $sectionRepository->getSingleton();
        $setting->setEnabled(!$setting->isEnabled());
        $em->flush();

        return $this->json(['ok' => true, 'enabled' => $setting->isEnabled()]);
    }

    #[Route('/parametres/partenaires/ajouter', name: 'manage_partners_add', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function add(Request $request, PartnerRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $name = trim((string) $request->request->get('name'));
        if ($name === '') {
            $this->addFlash('error', 'Le nom est obligatoire.');
            return $this->redirectToRoute('manage_partners');
        }

        $partner = new Partner();
        $partner->setName($name);
        $partner->setUrl($request->request->get('url') ?: null);
        $partner->setPosition($repository->findNextPosition());

        $logoFile = $request->files->get('logo');
        if ($logoFile) {
            $partner->setLogoFile($logoFile);
        }

        $em->persist($partner);
        $em->flush();

        $this->addFlash('success', 'Partenaire ajouté.');
        return $this->redirectToRoute('manage_partners');
    }

    #[Route('/parametres/partenaires/{id}/modifier', name: 'manage_partners_update', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(Partner $partner, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $partner->setName(trim((string) $request->request->get('name')));
        $partner->setUrl($request->request->get('url') ?: null);

        $logoFile = $request->files->get('logo');
        if ($logoFile) {
            $partner->setLogoFile($logoFile);
        }

        $em->flush();

        $this->addFlash('success', 'Partenaire mis à jour.');
        return $this->redirectToRoute('manage_partners');
    }

    #[Route('/parametres/partenaires/{id}/supprimer', name: 'manage_partners_remove', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function remove(Partner $partner, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($partner);
        $em->flush();

        $this->addFlash('success', 'Partenaire retiré.');
        return $this->redirectToRoute('manage_partners');
    }

    #[Route('/parametres/partenaires/{id}/basculer', name: 'manage_partners_toggle', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(Partner $partner, EntityManagerInterface $em): Response
    {
        $partner->setActive(!$partner->isActive());
        $em->flush();

        return $this->json(['ok' => true, 'active' => $partner->isActive()]);
    }

    #[Route('/parametres/partenaires/{id}/deplacer/{direction}', name: 'manage_partners_move', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function move(Partner $partner, string $direction, PartnerRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $items = $repository->findAllOrdered();
        $index = array_search($partner->getId(), array_map(fn ($p) => $p->getId(), $items), true);
        $swapWith = 'up' === $direction ? $index - 1 : $index + 1;

        if ($swapWith >= 0 && $swapWith < count($items)) {
            $a = $items[$index]->getPosition();
            $b = $items[$swapWith]->getPosition();
            $items[$index]->setPosition($b);
            $items[$swapWith]->setPosition($a);
            $em->flush();
        }

        return $this->redirectToRoute('manage_partners');
    }
}
