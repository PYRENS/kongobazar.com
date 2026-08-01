<?php

namespace App\Controller\Manage;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'manage_login', host: 'manage.kongobazar.com')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('manage/security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'spaceLabel' => 'Back-Office Administration',
            'accentColor' => '#1F4E3D',
        ]);
    }

    #[Route('/logout', name: 'manage_logout', host: 'manage.kongobazar.com')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode ne devrait jamais être exécutée — interceptée par le firewall.');
    }

}