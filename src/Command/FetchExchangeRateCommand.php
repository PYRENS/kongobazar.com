<?php
// src/Command/FetchExchangeRateCommand.php
namespace App\Command;

use App\Entity\ExchangeRate;
use App\Service\ExchangeRateFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:exchange-rate:fetch', description: 'Récupère le taux USD/CDF via API et le propose comme référence')]
class FetchExchangeRateCommand extends Command
{
    public function __construct(
        private readonly ExchangeRateFetcher $fetcher,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rate = $this->fetcher->fetchUsdToCdf();

        if (null === $rate) {
            $output->writeln('<error>Échec de récupération du taux via API.</error>');
            return Command::FAILURE;
        }

        $exchangeRate = new ExchangeRate();
        $exchangeRate->setRateUsdToCdf($rate);
        $exchangeRate->setSource('api');

        $this->em->persist($exchangeRate);
        $this->em->flush();

        $output->writeln("Taux enregistré : 1 USD = {$rate} CDF (source: api)");

        return Command::SUCCESS;
    }
}