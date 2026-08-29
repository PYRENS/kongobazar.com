<?php

namespace App\Controller\Manage;

use App\Entity\AdminSidebarTheme;
use App\Repository\AdminSidebarThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminSidebarThemeController extends AbstractController
{
    private const COLOR_FIELDS = [
        'bgColor', 'textColor', 'hoverBgColor', 'hoverTextColor',
        'activeBgColor', 'activeTextColor', 'iconColor',
    ];

    #[Route('/parametres/themes', name: 'manage_sidebar_themes_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(Request $request, AdminSidebarThemeRepository $repository): Response
    {
        $themes = $repository->findAllOrdered();

        $rows = array_map(fn (AdminSidebarTheme $theme) => [
            'theme' => $theme,
            'usageCount' => $repository->countUsersUsingTheme($theme->getId()),
        ], $themes);

        $sortField = $request->query->get('sort', 'name');
        $sortDir = $request->query->get('dir', 'ASC');
        $allowed = ['name', 'usageCount'];
        if (!in_array($sortField, $allowed, true)) {
            $sortField = 'name';
        }
        $multiplier = strtoupper($sortDir) === 'DESC' ? -1 : 1;
        usort($rows, function ($a, $b) use ($sortField, $multiplier) {
            $valA = $sortField === 'usageCount' ? $a['usageCount'] : $a['theme']->getName();
            $valB = $sortField === 'usageCount' ? $b['usageCount'] : $b['theme']->getName();
            return $multiplier * ($valA <=> $valB);
        });

        return $this->render('manage/sidebar_themes/index.html.twig', [
            'rows' => $rows,
            'stats' => [
                'total' => count($themes),
                'inUse' => count(array_filter($rows, fn ($r) => $r['usageCount'] > 0)),
            ],
            'currentSort' => $sortField,
            'currentDir' => $sortDir,
            'searchTerm' => $request->query->get('q', ''),
        ]);
    }

    #[Route('/parametres/themes/nouveau', name: 'manage_sidebar_themes_new', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('manage/sidebar_themes/form.html.twig', ['theme' => null]);
    }

    #[Route('/parametres/themes/nouveau', name: 'manage_sidebar_themes_create', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, AdminSidebarThemeRepository $repository): RedirectResponse
    {
        $name = trim((string) $request->request->get('name'));
        if ($name === '') {
            $this->addFlash('error', 'Le nom du thème est obligatoire.');
            return $this->redirectToRoute('manage_sidebar_themes_new');
        }
        if ($repository->findOneBy(['name' => $name])) {
            $this->addFlash('error', 'Un thème nommé "' . $name . '" existe déjà.');
            return $this->redirectToRoute('manage_sidebar_themes_new');
        }

        $theme = new AdminSidebarTheme();
        $theme->setName($name);
        $this->hydrate($theme, $request);
        $em->persist($theme);
        $em->flush();

        $this->addFlash('success', 'Thème "' . $name . '" créé.');
        return $this->redirectToRoute('manage_sidebar_themes_index');
    }

    #[Route('/parametres/themes/{id}/modifier', name: 'manage_sidebar_themes_edit', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function edit(AdminSidebarTheme $theme): Response
    {
        return $this->render('manage/sidebar_themes/form.html.twig', ['theme' => $theme]);
    }

    #[Route('/parametres/themes/{id}/modifier', name: 'manage_sidebar_themes_update', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(AdminSidebarTheme $theme, Request $request, EntityManagerInterface $em, AdminSidebarThemeRepository $repository): RedirectResponse
    {
        $name = trim((string) $request->request->get('name'));
        if ($name === '') {
            $this->addFlash('error', 'Le nom du thème est obligatoire.');
            return $this->redirectToRoute('manage_sidebar_themes_edit', ['id' => $theme->getId()]);
        }
        $existing = $repository->findOneBy(['name' => $name]);
        if ($existing && $existing->getId() !== $theme->getId()) {
            $this->addFlash('error', 'Un thème nommé "' . $name . '" existe déjà.');
            return $this->redirectToRoute('manage_sidebar_themes_edit', ['id' => $theme->getId()]);
        }

        $theme->setName($name);
        $this->hydrate($theme, $request);
        $em->flush();

        $this->addFlash('success', 'Thème "' . $name . '" mis à jour.');
        return $this->redirectToRoute('manage_sidebar_themes_index');
    }

    #[Route('/parametres/themes/{id}/supprimer', name: 'manage_sidebar_themes_delete', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(AdminSidebarTheme $theme, EntityManagerInterface $em): RedirectResponse
    {
        $name = $theme->getName();
        $em->remove($theme);
        $em->flush();

        $this->addFlash('success', 'Thème "' . $name . '" supprimé.');
        return $this->redirectToRoute('manage_sidebar_themes_index');
    }

    #[Route('/parametres/themes/{id}/choisir', name: 'manage_sidebar_themes_choose', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function choose(AdminSidebarTheme $theme, EntityManagerInterface $em): RedirectResponse
    {
        $user = $this->getUser();
        $user->setAdminSidebarTheme($theme);
        $em->flush();

        $this->addFlash('success', 'Thème "' . $theme->getName() . '" appliqué.');
        return $this->redirectToRoute('manage_settings');
    }

    private function hydrate(AdminSidebarTheme $theme, Request $request): void
    {
        $setters = [
            'bgColor' => 'setBgColor',
            'textColor' => 'setTextColor',
            'hoverBgColor' => 'setHoverBgColor',
            'hoverTextColor' => 'setHoverTextColor',
            'activeBgColor' => 'setActiveBgColor',
            'activeTextColor' => 'setActiveTextColor',
            'iconColor' => 'setIconColor',
        ];

        foreach (self::COLOR_FIELDS as $field) {
            $value = (string) $request->request->get($field, '#000000');
            $theme->{$setters[$field]}($value);
        }
    }
}