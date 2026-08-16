<?php

namespace App\Controller\Manage;

use App\Entity\SocialLink;
use App\Repository\SocialFloatSettingRepository;
use App\Repository\SocialLinkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SocialLinkManagementController extends AbstractController
{
    #[Route('/reseaux-sociaux', name: 'manage_social_links_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(SocialLinkRepository $repository, SocialFloatSettingRepository $settingRepository): Response
    {
        return $this->render('manage/social_links/index.html.twig', [
            'links' => $repository->findAllOrdered(),
            'setting' => $settingRepository->getSingleton(),
        ]);
    }

    #[Route('/reseaux-sociaux/visibilite', name: 'manage_social_links_visibility', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function saveVisibility(Request $request, SocialFloatSettingRepository $settingRepository, EntityManagerInterface $em): RedirectResponse
    {
        $setting = $settingRepository->getSingleton();
        $setting->setShowOnDesktop((bool) $request->request->get('show_desktop'));
        $setting->setShowOnTablet((bool) $request->request->get('show_tablet'));
        $setting->setShowOnMobile((bool) $request->request->get('show_mobile'));
        $em->flush();

        $this->addFlash('success', 'Visibilité enregistrée.');
        return $this->redirectToRoute('manage_social_links_index');
    }

    #[Route('/reseaux-sociaux/nouveau', name: 'manage_social_links_new', host: 'manage.kongobazar.com', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SocialLinkRepository $repository): Response
    {
        if ($request->isMethod('POST')) {
            $link = new SocialLink();
            $this->hydrate($link, $request);

            $maxPosition = 0;
            foreach ($repository->findAllOrdered() as $existing) {
                $maxPosition = max($maxPosition, $existing->getPosition());
            }
            $link->setPosition($maxPosition + 1);

            $em->persist($link);
            $em->flush();

            return $this->redirectToRoute('manage_social_links_index');
        }

        return $this->render('manage/social_links/form.html.twig', ['link' => null]);
    }

    #[Route('/reseaux-sociaux/{id}/modifier', name: 'manage_social_links_edit', host: 'manage.kongobazar.com', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em, SocialLinkRepository $repository): Response
    {
        $link = $repository->find($id) ?? throw $this->createNotFoundException();

        if ($request->isMethod('POST')) {
            $this->hydrate($link, $request);
            $em->flush();

            return $this->redirectToRoute('manage_social_links_index');
        }

        return $this->render('manage/social_links/form.html.twig', ['link' => $link]);
    }

    #[Route('/reseaux-sociaux/{id}/basculer', name: 'manage_social_links_toggle', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(int $id, SocialLinkRepository $repository, EntityManagerInterface $em): Response
    {
        $link = $repository->find($id);
        if ($link) {
            $link->setActive(!$link->isActive());
            $em->flush();
        }

        return $this->render('manage/social_links/_table.html.twig', ['links' => $repository->findAllOrdered()]);
    }

    #[Route('/reseaux-sociaux/{id}/deplacer/{direction}', name: 'manage_social_links_move', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function move(int $id, string $direction, SocialLinkRepository $repository, EntityManagerInterface $em): Response
    {
        $links = $repository->findAllOrdered();
        $ids = array_map(fn ($l) => $l->getId(), $links);
        $index = array_search($id, $ids, true);

        if (false !== $index) {
            $swapWith = 'up' === $direction ? $index - 1 : $index + 1;
            if (isset($links[$swapWith])) {
                $posA = $links[$index]->getPosition();
                $posB = $links[$swapWith]->getPosition();
                $links[$index]->setPosition($posB);
                $links[$swapWith]->setPosition($posA);
                $em->flush();
            }
        }

        return $this->render('manage/social_links/_table.html.twig', ['links' => $repository->findAllOrdered()]);
    }

    #[Route('/reseaux-sociaux/{id}/supprimer', name: 'manage_social_links_delete', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id, SocialLinkRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $link = $repository->find($id);
        if ($link) {
            $em->remove($link);
            $em->flush();
        }

        return $this->redirectToRoute('manage_social_links_index');
    }

    private function hydrate(SocialLink $link, Request $request): void
    {
        $link->setPlatform((string) $request->request->get('platform'));
        $link->setIconClass((string) $request->request->get('icon_class'));
        $link->setColorHex((string) $request->request->get('color_hex'));
        $link->setUrl((string) $request->request->get('url'));
        $link->setActive((bool) $request->request->get('active'));
    }
}