<?php

namespace App\Controller\Public;

use App\Entity\LegalAcceptance;
use App\Entity\User;
use App\Repository\LegalDocumentRepository;
use App\Repository\LegalDocumentVersionRepository;
use App\Repository\LoginBackgroundImageRepository;
use App\Repository\LoginBackgroundSectionSettingRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'public_register', host: 'kongobazar.com')]
    public function register(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        LoginBackgroundSectionSettingRepository $sectionSettingRepository,
        \Psr\Log\LoggerInterface $logger,
        LoginBackgroundImageRepository $imageRepository,
        LegalDocumentRepository $legalDocumentRepository,
        LegalDocumentVersionRepository $legalVersionRepository,
        MailerInterface $mailer,
    ): Response {
        $error = null;

        if ($request->isMethod('POST')) {
            $firstName = trim((string) $request->request->get('first_name', ''));
            $lastName = trim((string) $request->request->get('last_name', ''));
            $email = trim(strtolower((string) $request->request->get('email', '')));
            $password = (string) $request->request->get('password', '');
            $passwordConfirm = (string) $request->request->get('password_confirm', '');
            $termsAccepted = $request->request->getBoolean('terms_accepted');

            if ('' === $firstName || '' === $lastName) {
                $error = 'Le prénom et le nom sont obligatoires.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Adresse e-mail invalide.';
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
                $error = 'Le mot de passe doit contenir au moins 8 caractères, avec une majuscule, une minuscule et un chiffre.';
            } elseif ($password !== $passwordConfirm) {
                $error = 'Les mots de passe ne correspondent pas.';
            } elseif (!$termsAccepted) {
                $error = 'Tu dois accepter les conditions générales pour créer un compte.';
            } elseif ($userRepository->findOneBy(['email' => $email])) {
                $error = 'Un compte existe déjà avec cette adresse e-mail.';
            }

            if (null === $error) {
                $token = bin2hex(random_bytes(32));

                $user = new User();
                $user->setEmail($email);
                $user->setFirstName($firstName);
                $user->setLastName($lastName);
                $user->setRoles(['ROLE_BUYER']);
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->setStatus('pending'); // en attente de vérification de l'email
                $user->setVerificationToken($token);
                $user->setVerificationTokenExpiresAt((new \DateTimeImmutable())->modify('+48 hours'));

                $em->persist($user);

                // La case cochée ci-dessus vaut acceptation immédiate de la dernière version en vigueur
                // de chaque document obligatoire de l'espace Acheteur.
                foreach ($legalDocumentRepository->findAllOrdered() as $document) {
                    if ('public' !== $document->getTargetSpace() || !$document->isRequiresAcceptance()) {
                        continue;
                    }
                    $version = $legalVersionRepository->findLatestPublished($document);
                    if ($version) {
                        $em->persist(
                            (new LegalAcceptance())
                                ->setUser($user)
                                ->setVersion($version)
                                ->setIpAddress($request->getClientIp())
                        );
                    }
                }

                $em->flush();

                $emailSent = true;
                try {
                    $this->sendVerificationEmail($mailer, $user, $token);
                } catch (\Throwable $e) {
                    $emailSent = false;
                    $logger->error('Échec envoi email de vérification : ' . $e->getMessage());
                }

                return $this->render('public/security/register_success.html.twig', [
                    'email' => $user->getEmail(),
                    'emailSent' => $emailSent,
                ]);
            }
        }

        $sectionEnabled = $sectionSettingRepository->getSingleton()->isEnabled();

        return $this->render('public/security/register.html.twig', [
            'error' => $error,
            'old' => [
                'first_name' => $request->request->get('first_name', ''),
                'last_name' => $request->request->get('last_name', ''),
                'email' => $request->request->get('email', ''),
            ],
            'loginImage' => $sectionEnabled ? $imageRepository->pickActiveRandom('register') : null,
        ]);
    }

    private function sendVerificationEmail(MailerInterface $mailer, User $user, string $token): void
    {
        $link = $this->generateUrl('public_verify_email', ['token' => $token], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
        $firstName = htmlspecialchars($user->getFirstName() ?? '');

        $html = <<<HTML
            <div style="font-family: Arial, sans-serif; max-width: 480px; margin: 0 auto; padding: 32px 24px;">
                <p style="font-size: 20px; font-weight: 800; margin: 0 0 24px;">
                    <span style="color:#1a1a1a;">Kongo</span><span style="color:#2FA8E0;">Bazar</span>
                </p>
                <h1 style="font-size: 20px; margin: 0 0 16px;">Bienvenue {$firstName} !</h1>
                <p style="font-size: 14px; color: #333; line-height: 1.6;">
                    Confirme ton adresse e-mail pour activer ton compte et compléter ton profil.
                </p>
                <p style="text-align: center; margin: 28px 0;">
                    <a href="{$link}" style="background:#2FA8E0; color:#fff; text-decoration:none; padding:12px 28px; border-radius:30px; font-weight:700; font-size:14px; display:inline-block;">
                        Confirmer mon e-mail
                    </a>
                </p>
                <p style="font-size: 12px; color: #999;">Ce lien est valable 48 heures. Si tu n'es pas à l'origine de cette inscription, ignore cet e-mail.</p>
            </div>
            HTML;

        $mailer->send(
            (new Email())
                ->from(\App\Service\EmailAddresses::NO_REPLY)
                ->to($user->getEmail())
                ->subject('KongoBazar — Confirme ton adresse e-mail')
                ->html($html)
        );
    }
}