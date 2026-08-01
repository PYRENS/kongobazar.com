<?php

namespace App\Controller\Store;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'store_login', host: 'store.kongobazar.com')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('store/security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'spaceLabel' => 'Console Marchande',
            'accentColor' => '#C9992A',
        ]);
    }

    #[Route('/logout', name: 'store_logout', host: 'store.kongobazar.com')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode ne devrait jamais être exécutée — interceptée par le firewall.');
    }

    #[Route('/', name: 'store_home', host: 'store.kongobazar.com')]
    public function home(): Response
    {
        return new Response('<h1>Console Marchande</h1><p>Tableau de bord en construction. Vous êtes bien connecté.</p>');
    }
}