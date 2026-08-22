<?php

namespace App\Controller\Manage;

use App\Entity\EscrowTransaction;
use App\Entity\NegotiationThread;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\NegotiationThreadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NegotiationEscrowManagementController extends AbstractController
{
    #[Route('/negociations', name: 'manage_negotiations_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(Request $request, NegotiationThreadRepository $repository): Response
    {
        $buyerId = $request->query->get('buyer') ? (int) $request->query->get('buyer') : null;

        // Uniquement les négociations où un lien de paiement a été généré
        $threads = array_filter(
            $repository->findBy([], ['id' => 'DESC']),
            fn (NegotiationThread $t) => null !== $t->getPaymentLink()
                && (!$buyerId || $t->getBuyer()?->getId() === $buyerId)
        );

        return $this->render('manage/negotiations/index.html.twig', [
            'threads' => $threads,
            'currentBuyerId' => $buyerId,
        ]);
    }

    #[Route('/negociations/{id}/marquer-paye', name: 'manage_negotiations_mark_paid', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function markPaid(NegotiationThread $thread, EntityManagerInterface $em): RedirectResponse
    {
        $paymentLink = $thread->getPaymentLink();
        if (!$paymentLink || $paymentLink->isUsed()) {
            $this->addFlash('error', 'Aucun lien de paiement valide pour cette négociation.');
            return $this->redirectToRoute('manage_negotiations_index');
        }

        $product = $thread->getProduct();
        $seller = $product?->getSellerProfile();
        if (!$product || !$seller) {
            $this->addFlash('error', 'Produit ou vendeur introuvable pour cette négociation.');
            return $this->redirectToRoute('manage_negotiations_index');
        }

        $order = new Order();
        $order->setBuyer($thread->getBuyer());
        $order->setSellerProfile($seller);
        $order->setCheckoutGroup(bin2hex(random_bytes(16)));
        $order->setStatus('paid');
        $order->setTotalAmount($paymentLink->getAmount());
        $order->setCurrency('USD');
        $order->setTotalAmountUsd($paymentLink->getAmount());
        $order->setEscrowStatus('held');

        $item = new OrderItem();
        $item->setOrder($order);
        $item->setProduct($product);
        $item->setQuantity(1);
        $item->setUnitPrice($paymentLink->getAmount());
        $order->getItems()->add($item);

        $releaseCode = (string) random_int(100000, 999999);
        $authenticityCode = (string) random_int(1000, 9999);

        $escrow = new EscrowTransaction();
        $escrow->setOrder($order);
        $escrow->setAmountHeldUsd($paymentLink->getAmount());
        $escrow->setStatus('held');
        $escrow->setReleaseCode($releaseCode);
        $escrow->setAuthenticityCode($authenticityCode);

        $paymentLink->setUsed(true);
        $thread->setOrder($order);

        $em->persist($order);
        $em->persist($item);
        $em->persist($escrow);
        $em->flush();

        $this->addFlash('success', sprintf(
            'Paiement confirmé. Code d\'authenticité (à transmettre aux DEUX parties) : %s — Code de déblocage (à transmettre UNIQUEMENT à l\'acheteur) : %s',
            $authenticityCode,
            $releaseCode
        ));
        return $this->redirectToRoute('manage_negotiations_index');
    }

    #[Route('/negociations/escrow/{id}/liberer', name: 'manage_escrow_release', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function release(EscrowTransaction $escrow, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $enteredCode = trim((string) $request->request->get('code'));

        if ('held' !== $escrow->getStatus()) {
            $this->addFlash('error', 'Cette transaction n\'est pas en attente de déblocage.');
            return $this->redirectToRoute('manage_negotiations_index');
        }

        if ($enteredCode !== $escrow->getReleaseCode()) {
            $this->addFlash('error', 'Code incorrect.');
            return $this->redirectToRoute('manage_negotiations_index');
        }

        $escrow->setStatus('released');
        $escrow->setAmountReleasedUsd($escrow->getAmountHeldUsd());
        $escrow->setReleasedAt(new \DateTimeImmutable());
        $escrow->setReleaseCodeVerifiedAt(new \DateTimeImmutable());

        $order = $escrow->getOrder();
        $order->setEscrowStatus('released');

        $em->flush();

        $this->addFlash('success', 'Fonds débloqués vers le vendeur.');
        return $this->redirectToRoute('manage_negotiations_index');
    }
}