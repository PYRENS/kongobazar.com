<?php

namespace App\EventListener;

use App\Entity\LoginHistory;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
class LoginHistoryListener
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $request = $event->getRequest();

        $entry = new LoginHistory();
        $entry->setUser($user);
        $entry->setIpAddress($request->getClientIp());
        $entry->setUserAgent($request->headers->get('User-Agent'));
        $this->em->persist($entry);
        $this->em->flush();
    }
}