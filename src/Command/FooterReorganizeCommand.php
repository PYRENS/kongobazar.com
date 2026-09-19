<?php

namespace App\Command;

use App\Entity\CustomMenuItem;
use App\Repository\CustomMenuItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:footer:reorganize', description: 'Réorganise les rubriques du footer (fusion Service client/Aide/Contact + nouveaux liens)')]
class FooterReorganizeCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CustomMenuItemRepository $repository,
    ) {
        parent::__construct();
    }

    /**
     * Structure cible : [location => [ [label, url, openInNewTab], ... ]].
     * Colonnes 7 et 8 laissées vides volontairement — à définir ensuite.
     */
    private const STRUCTURE = [
        'footer_col_1' => [
            ['À propos', '/a-propos'],
            ['Blog', '/blog'],
        ],
        'footer_col_2' => [
            ['Mon compte', '/compte'],
            ['Mes commandes', '/compte/commandes'],
            ['Suivi de commande', '/suivi-commande'],
            ['Liste de souhaits', '/wishlist'],
        ],
        'footer_col_3' => [
            ['CGU', '/cgu'],
            ['CGV', '/cgv'],
            ['Confidentialité', '/politique-de-confidentialite'],
            ['Mentions légales', '/mentions-legales'],
        ],
        'footer_col_4' => [
            ['FAQ', '/faq'],
            ['Centre d\'aide', '/centre-aide'],
            ['Retours', '/retours'],
            ['Réclamations', '/reclamations'],
            ['Contactez-nous', '/contact'],
        ],
        'footer_col_5' => [
            ['Livraison', '/livraison'],
            ['Paiement', '/paiement'],
            ['Zones couvertes', '/zones-livraison'],
        ],
        'footer_col_6' => [
            ['Devenir vendeur', '/devenir-vendeur'],
            ['Devenir point relais', '/devenir-point-relais'],
            ['Devenir partenaire', '/devenir-partenaire'],
            ['Parrainage', '/parrainage'],
        ],
        'footer_col_7' => [],
        'footer_col_8' => [],
    ];

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        foreach (self::STRUCTURE as $location => $items) {
            // On retire tout ce qui existe déjà pour cet emplacement, pour repartir propre.
            $existing = $this->repository->findAllByLocation($location);
            foreach ($existing as $old) {
                $this->em->remove($old);
            }

            foreach ($items as $position => [$label, $url]) {
                $item = new CustomMenuItem();
                $item->setLocation($location);
                $item->setTargetSpace('public');
                $item->setLabel($label);
                $item->setUrl($url);
                $item->setOpenInNewTab(false);
                $item->setActive(true);
                $item->setPosition($position);
                $this->em->persist($item);
            }

            $io->writeln(sprintf('%s : %d lien(s)', $location, count($items)));
        }

        $this->em->flush();
        $io->success('Footer réorganisé. Colonnes 7 et 8 laissées vides — à définir ensemble.');

        return Command::SUCCESS;
    }
}
