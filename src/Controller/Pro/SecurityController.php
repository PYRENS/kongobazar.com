<?php

namespace App\Controller\Pro;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'pro_login', host: 'pro.kongobazar.com')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('pro/security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'spaceLabel' => 'Espace Pro',
            'accentColor' => '#C9992A',
        ]);
    }

    #[Route('/logout', name: 'pro_logout', host: 'pro.kongobazar.com')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode ne devrait jamais être exécutée — interceptée par le firewall.');
    }

    #[Route('/', name: 'pro_home', host: 'pro.kongobazar.com')]
    public function home(): Response
    {
        return new Response('<h1>Espace Pro</h1><p>Tableau de bord en construction. Vous êtes bien connecté.</p>');
    }
}