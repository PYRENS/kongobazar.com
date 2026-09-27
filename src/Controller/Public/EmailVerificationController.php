<?php

namespace App\Controller\Public;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\EmailAddresses;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class EmailVerificationController extends AbstractController
{
    #[Route('/email/confirmer/{token}', name: 'public_verify_email', host: 'kongobazar.com')]
    public function verify(string $token, UserRepository $userRepository, EntityManagerInterface $em, MailerInterface $mailer): RedirectResponse
    {
        $user = $userRepository->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Ce lien de confirmation est invalide.');
            return $this->redirectToRoute('public_home');
        }

        if ($user->getVerificationTokenExpiresAt() && $user->getVerificationTokenExpiresAt() < new \DateTimeImmutable()) {
            $this->addFlash('error', 'Ce lien de confirmation a expiré.');
            return $this->redirectToRoute('public_home');
        }

        $user->setStatus('active');
        $user->setVerificationToken(null);
        $user->setVerificationTokenExpiresAt(null);
        $em->flush();

        $this->sendWelcomeEmail($mailer, $user);

        $this->addFlash('success', 'Ton adresse e-mail est confirmée !');

        return $this->redirectToRoute('public_complete_profile');
    }

    private function sendWelcomeEmail(MailerInterface $mailer, User $user): void
    {
        $firstName = htmlspecialchars($user->getFirstName() ?? '');
        $homeLink = $this->generateUrl('public_home', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

        $html = <<<HTML
            <div style="font-family: Arial, sans-serif; max-width: 480px; margin: 0 auto; padding: 32px 24px;">
                <p style="font-size: 20px; font-weight: 800; margin: 0 0 24px;">
                    <span style="color:#1a1a1a;">Kongo</span><span style="color:#2FA8E0;">Bazar</span>
                </p>
                <h1 style="font-size: 20px; margin: 0 0 16px;">Bienvenue chez KongoBazar, {$firstName} !</h1>
                <p style="font-size: 14px; color: #333; line-height: 1.6;">
                    Ton compte est maintenant activé. Tu peux dès à présent parcourir le catalogue, profiter des offres
                    et suivre tes commandes depuis ton espace client.
                </p>
                <p style="text-align: center; margin: 28px 0;">
                    <a href="{$homeLink}" style="background:#2FA8E0; color:#fff; text-decoration:none; padding:12px 28px; border-radius:30px; font-weight:700; font-size:14px; display:inline-block;">
                        Découvrir KongoBazar
                    </a>
                </p>
                <p style="font-size: 12px; color: #999;">À bientôt sur KongoBazar !</p>
            </div>
            HTML;

        $mailer->send(
            (new Email())
                ->from(EmailAddresses::NO_REPLY)
                ->to($user->getEmail())
                ->subject('Bienvenue sur KongoBazar — ton compte est activé')
                ->html($html)
        );
    }
}