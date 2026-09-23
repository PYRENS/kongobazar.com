<?php

namespace App\Controller\Manage;

use App\Entity\CustomMenuItem;
use App\Repository\CustomMenuItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FooterMenuController extends AbstractController
{
    public const LOCATIONS = [
        'footer_col_1' => 'KongoBazar',
        'footer_col_2' => 'Mon compte',
        'footer_col_3' => 'Informations légales',
        'footer_col_4' => 'Service client',
        'footer_col_5' => 'Infos pratiques',
        'footer_col_6' => 'Gagnez plus',
        'footer_col_7' => 'Aide et support',
        'footer_col_8' => 'Contact',
        'footer_bottom_links' => 'Liens rapides (bas de page)',
    ];

    #[Route('/parametres/footer/rubriques', name: 'manage_footer_menu_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(CustomMenuItemRepository $repository): Response
    {
        $itemsByLocation = [];
        foreach (self::LOCATIONS as $location => $label) {
            $itemsByLocation[$location] = $repository->findAllByLocation($location);
        }

        return $this->render('manage/footer_menu/index.html.twig', [
            'locations' => self::LOCATIONS,
            'itemsByLocation' => $itemsByLocation,
        ]);
    }

    #[Route('/parametres/footer/rubriques/{location}/ajouter', name: 'manage_footer_menu_add', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function add(string $location, Request $request, CustomMenuItemRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        if (!isset(self::LOCATIONS[$location])) {
            throw $this->createNotFoundException();
        }

        $label = trim((string) $request->request->get('label', ''));
        $url = trim((string) $request->request->get('url', ''));
        if ('' === $label || '' === $url) {
            $this->addFlash('error', 'Le libellé et le lien sont obligatoires.');
            return $this->redirectToRoute('manage_footer_menu_index');
        }

        $item = new CustomMenuItem();
        $item->setLocation($location);
        $item->setTargetSpace('public');
        $item->setLabel($label);
        $item->setUrl($url);
        $item->setOpenInNewTab((bool) $request->request->get('open_in_new_tab'));
        $item->setActive(true);
        $item->setPosition($repository->findNextPosition($location));

        $em->persist($item);
        $em->flush();

        $this->addFlash('success', 'Lien ajouté.');
        return $this->redirectToRoute('manage_footer_menu_index');
    }

    #[Route('/parametres/footer/rubriques/{id}/modifier', name: 'manage_footer_menu_edit', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function edit(CustomMenuItem $item, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $label = trim((string) $request->request->get('label', ''));
        $url = trim((string) $request->request->get('url', ''));
        if ('' === $label || '' === $url) {
            $this->addFlash('error', 'Le libellé et le lien sont obligatoires.');
            return $this->redirectToRoute('manage_footer_menu_index');
        }

        $item->setLabel($label);
        $item->setUrl($url);
        $item->setOpenInNewTab((bool) $request->request->get('open_in_new_tab'));
        $em->flush();

        $this->addFlash('success', 'Lien mis à jour.');
        return $this->redirectToRoute('manage_footer_menu_index');
    }

    #[Route('/parametres/footer/rubriques/{id}/supprimer', name: 'manage_footer_menu_remove', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function remove(CustomMenuItem $item, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($item);
        $em->flush();

        $this->addFlash('success', 'Lien retiré.');
        return $this->redirectToRoute('manage_footer_menu_index');
    }

    #[Route('/parametres/footer/rubriques/{id}/basculer', name: 'manage_footer_menu_toggle', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(CustomMenuItem $item, EntityManagerInterface $em): Response
    {
        $item->setActive(!$item->isActive());
        $em->flush();

        return $this->json(['ok' => true, 'active' => $item->isActive()]);
    }

    #[Route('/parametres/footer/rubriques/{id}/deplacer/{direction}', name: 'manage_footer_menu_move', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function move(CustomMenuItem $item, string $direction, CustomMenuItemRepository $repository, EntityManagerInterface $em): Response
    {
        $items = $repository->findAllByLocation($item->getLocation());
        $index = array_search($item->getId(), array_map(fn ($i) => $i->getId(), $items), true);
        $swapWith = 'up' === $direction ? $index - 1 : $index + 1;

        if ($swapWith >= 0 && $swapWith < count($items)) {
            // On échange les 2 éléments dans le tableau, PUIS on réattribue des
            // positions 0,1,2,3... à toute la liste — ceci corrige aussi
            // définitivement d'éventuelles positions en doublon préexistantes,
            // qui rendaient un simple échange de valeurs sans effet visible.
            [$items[$index], $items[$swapWith]] = [$items[$swapWith], $items[$index]];
            foreach ($items as $i => $sortedItem) {
                $sortedItem->setPosition($i);
            }
            $em->flush();
        }

        return $this->json([
            'ok' => true,
            'order' => array_map(fn ($i) => $i->getId(), $items),
        ]);
    }
}
