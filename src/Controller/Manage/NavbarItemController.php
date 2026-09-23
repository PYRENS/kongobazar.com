<?php

namespace App\Controller\Manage;

use App\Entity\CustomMenuItem;
use App\Repository\CustomMenuItemRepository;
use App\Service\NavbarItemProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NavbarItemController extends AbstractController
{
    /** Pages du site proposées pour un lien personnalisé (sinon : adresse libre). */
    public const INTERNAL_ROUTES = [
        'network_index' => 'Notre réseau',
        'blog_index' => 'Blog',
        'catalog_offers_individual' => 'Offres — Particuliers',
        'catalog_offers_professional' => 'Offres — Professionnels',
        'catalog_offers_store' => 'Offres — Magasins',
        'catalog_offers_kongobazar' => 'Offres — KongoBazar',
    ];

    #[Route('/parametres/navbar', name: 'manage_navbar_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(NavbarItemProvider $provider): Response
    {
        return $this->render('manage/navbar/index.html.twig', [
            'items' => $provider->getAllItems(),
            'internalRoutes' => self::INTERNAL_ROUTES,
        ]);
    }

    #[Route('/parametres/navbar/ajouter', name: 'manage_navbar_new', host: 'manage.kongobazar.com', methods: ['GET', 'POST'])]
    public function new(Request $request, NavbarItemProvider $provider, CustomMenuItemRepository $repository, EntityManagerInterface $em): Response
    {
        $provider->getAllItems(); // garantit que les éléments système existent avant de calculer la position

        return $this->handleForm(null, $request, $repository, $em);
    }

    #[Route('/parametres/navbar/{id}/modifier', name: 'manage_navbar_edit', host: 'manage.kongobazar.com', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(CustomMenuItem $item, Request $request, CustomMenuItemRepository $repository, EntityManagerInterface $em): Response
    {
        if (!$this->isNavbarItem($item) || $item->isSystem()) {
            $this->addFlash('error', 'Cet élément est géré par le site : seuls son ordre et son affichage sont modifiables.');

            return $this->redirectToRoute('manage_navbar_index');
        }

        return $this->handleForm($item, $request, $repository, $em);
    }

    #[Route('/parametres/navbar/{id}/supprimer', name: 'manage_navbar_delete', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(CustomMenuItem $item, EntityManagerInterface $em): RedirectResponse
    {
        if (!$this->isNavbarItem($item) || $item->isSystem()) {
            $this->addFlash('error', 'Cet élément ne peut pas être supprimé : tu peux seulement le masquer.');

            return $this->redirectToRoute('manage_navbar_index');
        }

        $em->remove($item);
        $em->flush();

        $this->addFlash('success', 'Élément supprimé.');

        return $this->redirectToRoute('manage_navbar_index');
    }

    #[Route('/parametres/navbar/{id}/basculer', name: 'manage_navbar_toggle', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(CustomMenuItem $item, EntityManagerInterface $em): Response
    {
        if (!$this->isNavbarItem($item)) {
            throw $this->createNotFoundException();
        }

        $item->setActive(!$item->isActive());
        $em->flush();

        return $this->json(['ok' => true, 'active' => $item->isActive()]);
    }

    #[Route('/parametres/navbar/{id}/basculer-mobile', name: 'manage_navbar_toggle_mobile', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleMobile(CustomMenuItem $item, EntityManagerInterface $em): Response
    {
        if (!$this->isNavbarItem($item)) {
            throw $this->createNotFoundException();
        }

        // "Tous les rayons" est le tiroir lui-même : pas d'option mobile
        if ('all_rayons' === $item->getSystemKey()) {
            return $this->json(['ok' => false, 'showInMobileMenu' => $item->isShowInMobileMenu()]);
        }

        $item->setShowInMobileMenu(!$item->isShowInMobileMenu());
        $em->flush();

        return $this->json(['ok' => true, 'showInMobileMenu' => $item->isShowInMobileMenu()]);
    }

    #[Route('/parametres/navbar/{id}/deplacer/{direction}', name: 'manage_navbar_move', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function move(CustomMenuItem $item, string $direction, NavbarItemProvider $provider, EntityManagerInterface $em): Response
    {
        if (!$this->isNavbarItem($item)) {
            throw $this->createNotFoundException();
        }

        $items = $provider->getAllItems();
        $index = array_search($item->getId(), array_map(static fn ($i) => $i->getId(), $items), true);
        $swapWith = 'up' === $direction ? $index - 1 : $index + 1;

        if (false !== $index && $swapWith >= 0 && $swapWith < count($items)) {
            // On échange les 2 éléments, puis on réattribue des positions 0,1,2… à toute la liste
            [$items[$index], $items[$swapWith]] = [$items[$swapWith], $items[$index]];
            foreach ($items as $i => $sortedItem) {
                $sortedItem->setPosition($i);
            }
            $em->flush();
        }

        return $this->json([
            'ok' => true,
            'order' => array_map(static fn ($i) => $i->getId(), $items),
        ]);
    }

    private function isNavbarItem(CustomMenuItem $item): bool
    {
        return NavbarItemProvider::LOCATION === $item->getLocation() && NavbarItemProvider::SPACE === $item->getTargetSpace();
    }

    private function handleForm(?CustomMenuItem $item, Request $request, CustomMenuItemRepository $repository, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $label = trim((string) $request->request->get('label', ''));
            $route = (string) $request->request->get('internal_route', '');
            $url = trim((string) $request->request->get('url', ''));
            $useRoute = isset(self::INTERNAL_ROUTES[$route]);

            $error = null;
            if ('' === $label || mb_strlen($label) > 100) {
                $error = 'Le libellé est obligatoire (100 caractères maximum).';
            } elseif (!$useRoute && !preg_match('#^(/|https?://)#i', $url)) {
                $error = 'Choisis une page du site, ou saisis un lien qui commence par / ou https://.';
            }

            if (null === $error) {
                $isNew = null === $item;
                if ($isNew) {
                    $item = (new CustomMenuItem())
                        ->setLocation(NavbarItemProvider::LOCATION)
                        ->setTargetSpace(NavbarItemProvider::SPACE)
                        ->setPosition($repository->findNextPosition(NavbarItemProvider::LOCATION, NavbarItemProvider::SPACE));
                }

                $item->setLabel($label);
                $item->setInternalRoute($useRoute ? $route : null);
                $item->setUrl($useRoute ? null : mb_substr($url, 0, 255));
                $item->setOpenInNewTab($request->request->getBoolean('open_in_new_tab'));
                $item->setActive($request->request->getBoolean('active'));
                $item->setShowInMobileMenu($request->request->getBoolean('show_in_mobile_menu'));

                if ($isNew) {
                    $em->persist($item);
                }
                $em->flush();

                $this->addFlash('success', $isNew ? 'Élément ajouté au navbar.' : 'Élément mis à jour.');

                return $this->redirectToRoute('manage_navbar_index');
            }

            $this->addFlash('error', $error);
        }

        return $this->render('manage/navbar/form.html.twig', [
            'item' => $item,
            'internalRoutes' => self::INTERNAL_ROUTES,
        ]);
    }
}