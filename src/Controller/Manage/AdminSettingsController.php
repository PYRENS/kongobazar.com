<?php

namespace App\Controller\Manage;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminSettingsController extends AbstractController
{
    #[Route('/parametres', name: 'manage_settings', host: 'manage.kongobazar.com')]
    public function index(): Response
    {
        return $this->render('manage/settings.html.twig');
    }

    #[Route('/parametres/appliquer', name: 'manage_settings_save', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function save(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $user = $this->getUser();
        $user->setAdminSidebarColor($request->request->get('sidebar_color'));
        $user->setAdminDarkMode((bool) $request->request->get('dark_mode'));
        $em->flush();

        $this->addFlash('success', 'Préférences enregistrées.');
        return $this->redirectToRoute('manage_settings');
    }

    #[Route('/parametres/mode-nuit-rapide', name: 'manage_settings_quick_darkmode', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function quickDarkMode(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $user->setAdminDarkMode((bool) $request->request->get('dark_mode'));
        $em->flush();

        return new Response('OK');
    }

}