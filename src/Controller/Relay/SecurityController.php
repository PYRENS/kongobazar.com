<?php

namespace App\Controller\Relay;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'relay_login', host: 'relay.kongobazar.com')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('relay/security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'spaceLabel' => 'Interface Agent Relais',
            'accentColor' => '#43a047',
        ]);
    }

    #[Route('/logout', name: 'relay_logout', host: 'relay.kongobazar.com')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode ne devrait jamais être exécutée — interceptée par le firewall.');
    }

    #[Route('/', name: 'relay_home', host: 'relay.kongobazar.com')]
    public function home(): Response
    {
        return new Response('<h1>Interface Agent Relais</h1><p>Tableau de bord en construction. Vous êtes bien connecté.</p>');
    }
}