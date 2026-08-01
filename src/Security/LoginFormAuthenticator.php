<?php

namespace App\Security;

use App\Entity\User;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    private const SPACE_ROUTES = [
        'manage.kongobazar.com' => ['login' => 'manage_login', 'home' => 'manage_home'],
        'pro.kongobazar.com'    => ['login' => 'pro_login',    'home' => 'pro_home'],
        'store.kongobazar.com'  => ['login' => 'store_login',  'home' => 'store_home'],
        'relay.kongobazar.com'  => ['login' => 'relay_login',  'home' => 'relay_home'],
    ];

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly EntityManagerInterface $em,
        private readonly CartService $cartService,
    ) {
    }

    private function getSpaceRoutes(Request $request): array
    {
        return self::SPACE_ROUTES[$request->getHost()] ?? ['login' => 'public_login', 'home' => 'public_home'];
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if ($user instanceof User) {
            $guestToken = $request->getSession()->get('_cart_token');
            if ($guestToken) {
                $this->cartService->mergeGuestCartIntoUser($guestToken, $user);
                $request->getSession()->remove('_cart_token');
            }
        }

        $routes = $this->getSpaceRoutes($request);

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate($routes['home']));
    }

    protected function getLoginUrl(Request $request): string
    {
        $routes = $this->getSpaceRoutes($request);

        return $this->urlGenerator->generate($routes['login']);
    }
}