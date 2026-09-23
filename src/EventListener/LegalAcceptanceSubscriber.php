<?php

namespace App\EventListener;

use App\Entity\User;
use App\Repository\LegalAcceptanceRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Bloque la navigation d'un client connecté (espace Acheteur) tant qu'il n'a pas accepté la
 * dernière version publiée de chaque document légal bloquant (CGV, CGU...).
 */
class LegalAcceptanceSubscriber implements EventSubscriberInterface
{
    /** Routes toujours accessibles, même avec une acceptation en attente. */
    private const ALLOWED_ROUTES = ['public_legal_pending', 'public_legal_accept', 'public_logout'];

    public function __construct(
        private readonly Security $security,
        private readonly LegalAcceptanceRepository $acceptanceRepository,
        private readonly RouterInterface $router,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Priorité volontairement basse : doit s'exécuter APRÈS que Symfony ait restauré la
        // connexion du client depuis la session (le composant de sécurité tourne à la priorité 8) ;
        // sinon $this->security->getUser() renvoie null même si le client est bien connecté.
        return [KernelEvents::REQUEST => ['onKernelRequest', -10]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ('kongobazar.com' !== $request->getHost()) {
            return;
        }

        $routeName = $request->attributes->get('_route');
        if (in_array($routeName, self::ALLOWED_ROUTES, true) || str_starts_with((string) $routeName, '_')) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $pending = $this->acceptanceRepository->findPendingVersionsForUser($user, 'public');
        if (!$pending) {
            return;
        }

        $request->getSession()->set('legal_redirect_after_accept', $request->getPathInfo());

        $event->setResponse(new RedirectResponse(
            $this->router->generate('public_legal_pending', [], UrlGeneratorInterface::ABSOLUTE_PATH)
        ));
    }
}