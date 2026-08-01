<?php

namespace App\Command;

use App\Entity\Notification;
use App\Entity\WishlistAlert;
use App\Repository\WishlistAlertRepository;
use App\Repository\WishlistItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:wishlist:check-stock-alerts', description: 'Vérifie les articles en wishlist et envoie des alertes stock/prix')]
class WishlistCheckStockAlertsCommand extends Command
{
    public function __construct(
        private readonly WishlistItemRepository $wishlistItemRepository,
        private readonly WishlistAlertRepository $wishlistAlertRepository,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $items = $this->wishlistItemRepository->findAllWithVariant();
        $created = 0;

        foreach ($items as $item) {
            $variant = $item->getVariant();
            $product = $variant->getProduct();

            // Rupture de stock
            if (!$variant->isInStock() && !$this->wishlistAlertRepository->hasRecentAlert($item, 'out_of_stock')) {
                $this->createAlert($item, 'out_of_stock');
                $created++;
            }
            // Stock faible (mais pas rupture)
            elseif ($variant->isInStock() && $variant->isLowStock() && !$this->wishlistAlertRepository->hasRecentAlert($item, 'low_stock')) {
                $this->createAlert($item, 'low_stock');
                $created++;
            }

            // Nouvelle promo active
            $activeDiscount = $product->getActiveDiscountCampaign();
            if ($activeDiscount && !$this->wishlistAlertRepository->hasRecentAlert($item, 'discount')) {
                $this->createAlert($item, 'discount');
                $created++;
            }
        }

        $this->em->flush();
        $output->writeln("{$created} alerte(s) créée(s).");

        return Command::SUCCESS;
    }

    private function createAlert(\App\Entity\WishlistItem $item, string $type): void
    {
        $alert = new WishlistAlert();
        $alert->setWishlistItem($item);
        $alert->setType($type);
        $alert->setSentAt(new \DateTimeImmutable());
        $this->em->persist($alert);

        $notification = new Notification();
        $notification->setUser($item->getUser());
        $notification->setChannel('email');
        $notification->setType('wishlist_' . $type);
        $notification->setPayload([
            'productTitle' => $item->getVariant()->getProduct()->getTitle(),
        ]);
        $this->em->persist($notification);
    }
}