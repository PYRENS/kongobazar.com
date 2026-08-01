<?php

namespace App\EventListener;

use App\Entity\Notification;
use App\Entity\Product;
use App\Repository\ProductAvailabilityAlertRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Product::class)]
#[AsDoctrineListener(event: Events::postFlush)]
class ProductAvailabilityListener
{
    /** @var Product[] */
    private array $productsJustBecameAvailable = [];

    public function __construct(
        private readonly ProductAvailabilityAlertRepository $alertRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function preUpdate(Product $product, PreUpdateEventArgs $event): void
    {
        if (!$event->hasChangedField('status')) {
            return;
        }

        $oldStatus = $event->getOldValue('status');
        $newStatus = $event->getNewValue('status');

        if ($oldStatus === 'coming_soon' && $newStatus === 'active') {
            // On ne fait QUE repérer le produit ici — aucune écriture en base pour l'instant
            $this->productsJustBecameAvailable[] = $product;
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (empty($this->productsJustBecameAvailable)) {
            return;
        }

        $products = $this->productsJustBecameAvailable;
        $this->productsJustBecameAvailable = []; // évite un double traitement

        $hasNew = false;

        foreach ($products as $product) {
            $pendingAlerts = $this->alertRepository->findPendingForProduct($product);

            foreach ($pendingAlerts as $alert) {
                $notification = new Notification();
                $notification->setUser($alert->getUser());
                $notification->setChannel('email');
                $notification->setType('product_available');
                $notification->setPayload([
                    'productId' => $product->getId(),
                    'productTitle' => $product->getTitle(),
                ]);
                $this->em->persist($notification);

                $alert->setNotifiedAt(new \DateTimeImmutable());
                $hasNew = true;
            }
        }

        if ($hasNew) {
            $this->em->flush();
        }
    }
}