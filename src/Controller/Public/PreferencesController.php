<?php

namespace App\Controller\Public;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class PreferencesController extends AbstractController
{
    #[Route('/preferences/set', name: 'set_preferences', host: 'kongobazar.com', methods: ['POST'])]
    public function setPreferences(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $session = $request->getSession();

        $locale = $request->request->get('locale', $session->get('_locale', 'fr'));
        $currency = $request->request->get('currency', $session->get('_currency', 'USD'));

        $session->set('_locale', $locale);
        $session->set('_currency', $currency);

        if ($user = $this->getUser()) {
            $user->setPreferredLocale($locale);
            $user->setPreferredCurrency($currency);
            $em->flush();
        }

        return $this->redirect($request->headers->get('referer', $this->generateUrl('public_home')));
    }
}