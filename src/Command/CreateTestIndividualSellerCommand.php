<?php

namespace App\Command;

use App\Entity\IndividualProfile;
use App\Entity\User;
use App\Service\SellerReferenceNumberGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Crée un compte vendeur "Particulier" de test — utile tant que le vrai flux
 * d'inscription/validation vendeur n'existe pas encore.
 *
 * Usage : php bin/console app:create-test-individual-seller [email] [mot-de-passe]
 * Par défaut : particulier.test@kongobazar.com / password123
 */
#[AsCommand(name: 'app:create-test-individual-seller', description: 'Crée un compte vendeur "Particulier" de test')]
class CreateTestIndividualSellerCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly SellerReferenceNumberGenerator $referenceGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Email du compte', 'particulier.test@kongobazar.com')
            ->addArgument('password', InputArgument::OPTIONAL, 'Mot de passe', 'password123');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            $io->error("Un compte existe déjà avec cet email : {$email}");
            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setFirstName('Particulier');
        $user->setLastName('Test');
        $user->setRoles(['ROLE_SELLER']);
        $this->em->persist($user);

        $seller = new IndividualProfile();
        $seller->setUser($user);
        $seller->setStatus('active');
        $seller->setDisplayName('Particulier Test');
        $seller->setSlug('particulier-test-' . substr(md5(uniqid()), 0, 6));
        $seller->setReferenceNumber($this->referenceGenerator->generateFor($seller));
        $this->em->persist($seller);

        $this->em->flush();

        $io->success("Compte vendeur Particulier créé : {$email} / {$password} — référence {$seller->getReferenceNumber()}");
        $io->note('Pense à lui associer au moins un produit actif (status = active) pour tester "Particulier" sur l\'accueil.');

        return Command::SUCCESS;
    }
}
