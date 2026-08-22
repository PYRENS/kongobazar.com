<?php

namespace App\Controller\Manage;

use App\Repository\AdminSidebarThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminSettingsController extends AbstractController
{
    #[Route('/parametres', name: 'manage_settings', host: 'manage.kongobazar.com')]
    public function index(AdminSidebarThemeRepository $themeRepository): Response
    {
        return $this->render('manage/settings.html.twig', [
            'themes' => $themeRepository->findAllOrdered(),
        ]);
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