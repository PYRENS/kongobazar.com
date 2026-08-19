<?php

namespace App\Command;

use App\Repository\DiscountCampaignRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Fait vivre les statuts des remises vendeur (DiscountCampaign) :
 * scheduled -> active dès que startAt est atteint,
 * active -> expired dès que endAt est dépassé (le prix redevient
 * automatiquement normal, puisque plus aucune campagne "active"
 * n'est retournée par Product::getActiveDiscountCampaign()).
 *
 * À planifier en tâche cron toutes les 5 minutes, par exemple :
 * every 5 minutes
 * php bin/console app:discount-campaigns:sync-status
 */
#[AsCommand(
    name: 'app:discount-campaigns:sync-status',
    description: 'Active les remises programmées arrivées à échéance et expire celles qui sont terminées',
)]
class SyncDiscountCampaignStatusCommand extends Command
{
    public function __construct(
        private readonly DiscountCampaignRepository $repository,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $toActivate = $this->repository->findScheduledToActivate();
        foreach ($toActivate as $campaign) {
            $campaign->setStatus('active');
        }

        $toExpire = $this->repository->findActiveToExpire();
        foreach ($toExpire as $campaign) {
            $campaign->setStatus('expired');
        }

        $this->em->flush();

        $io->success(sprintf(
            '%d remise(s) activée(s), %d remise(s) expirée(s).',
            count($toActivate),
            count($toExpire)
        ));

        return Command::SUCCESS;
    }
}