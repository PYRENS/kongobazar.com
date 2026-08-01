<?php

namespace App\Controller\Public;

use App\Entity\NewsletterSubscriber;
use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class NewsletterController extends AbstractController
{
    #[Route('/newsletter/subscribe', name: 'newsletter_subscribe', host: 'kongobazar.com', methods: ['POST'])]
    public function subscribe(
        Request $request,
        NewsletterSubscriberRepository $repository,
        EntityManagerInterface $em,
    ): RedirectResponse {
        $email = $request->request->get('email');

        if ($email) {
            $existing = $repository->findOneBy(['email' => $email]);
            if (null === $existing) {
                $subscriber = new NewsletterSubscriber();
                $subscriber->setEmail($email);
                if ($user = $this->getUser()) {
                    $subscriber->setUser($user);
                }
                $em->persist($subscriber);
                $em->flush();
            }
        }

        return $this->redirect($request->headers->get('referer', $this->generateUrl('public_home')));
    }
}