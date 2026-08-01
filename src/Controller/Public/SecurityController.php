<?php

namespace App\Controller\Public;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'public_login', host: 'kongobazar.com')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('public/security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'spaceLabel' => 'Espace Acheteur',
            'accentColor' => '#2FA8E0',
        ]);
    }

    #[Route('/logout', name: 'public_logout', host: 'kongobazar.com')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode ne devrait jamais être exécutée — interceptée par le firewall.');
    }
}