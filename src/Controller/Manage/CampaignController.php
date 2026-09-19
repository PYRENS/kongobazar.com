<?php

namespace App\Controller\Manage;

use App\Entity\Campaign;
use App\Repository\CampaignRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CampaignController extends AbstractController
{
    #[Route('/campagnes', name: 'manage_campaign_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(CampaignRepository $repository, ProductRepository $productRepository, EntityManagerInterface $em): Response
    {
        $campaigns = $repository->findAllOrdered();

        $stats = [];
        foreach ($campaigns as $campaign) {
            $articleCount = 'solde' === $campaign->getType() ? count($productRepository->findAllActiveDeals()) : null;

            $soldCount = $em->createQueryBuilder()
                ->select('COALESCE(SUM(oi.quantity), 0)')
                ->from(\App\Entity\OrderItem::class, 'oi')
                ->join('oi.order', 'o')
                ->andWhere('o.createdAt >= :start')
                ->andWhere('o.createdAt < :end')
                ->setParameter('start', $campaign->getStartAt())
                ->setParameter('end', $campaign->getEndAt())
                ->getQuery()
                ->getSingleScalarResult();

            $stats[$campaign->getId()] = [
                'articleCount' => $articleCount,
                'soldCount' => (int) $soldCount,
            ];
        }

        return $this->render('manage/campaign/index.html.twig', [
            'campaigns' => $campaigns,
            'stats' => $stats,
            'types' => Campaign::TYPES,
        ]);
    }

    #[Route('/campagnes/ajouter', name: 'manage_campaign_new', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('manage/campaign/form.html.twig', ['campaign' => null, 'types' => Campaign::TYPES]);
    }

    #[Route('/campagnes/{id}/modifier', name: 'manage_campaign_edit', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function edit(Campaign $campaign): Response
    {
        return $this->render('manage/campaign/form.html.twig', ['campaign' => $campaign, 'types' => Campaign::TYPES]);
    }

    #[Route('/campagnes/enregistrer', name: 'manage_campaign_create', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        return $this->save(new Campaign(), $request, $em, true);
    }

    #[Route('/campagnes/{id}/enregistrer', name: 'manage_campaign_update', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(Campaign $campaign, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        return $this->save($campaign, $request, $em, false);
    }

    private function save(Campaign $campaign, Request $request, EntityManagerInterface $em, bool $isNew): RedirectResponse
    {
        $title = trim((string) $request->request->get('title', ''));
        $startAt = $request->request->get('start_at');
        $endAt = $request->request->get('end_at');

        if ('' === $title || !$startAt || !$endAt) {
            $this->addFlash('error', 'Le titre et la période (début/fin) sont obligatoires.');
            return $this->redirectToRoute($isNew ? 'manage_campaign_new' : 'manage_campaign_edit', $isNew ? [] : ['id' => $campaign->getId()]);
        }

        $startAtDate = new \DateTimeImmutable($startAt);
        $endAtDate = new \DateTimeImmutable($endAt);
        if ($endAtDate <= $startAtDate) {
            $this->addFlash('error', 'La date de fin doit être après la date de début.');
            return $this->redirectToRoute($isNew ? 'manage_campaign_new' : 'manage_campaign_edit', $isNew ? [] : ['id' => $campaign->getId()]);
        }

        $campaign->setType((string) $request->request->get('type', 'solde'));
        $campaign->setCustomTypeLabel($request->request->get('custom_type_label') ?: null);
        $campaign->setTitle($title);
        $campaign->setStartAt($startAtDate);
        $campaign->setEndAt($endAtDate);
        $campaign->setBatchSize((int) $request->request->get('batch_size', 12));

        /** @var UploadedFile|null $file */
        $file = $request->files->get('banner_image');
        if ($file) {
            $campaign->setBannerImageFile($file);
        }

        if ($isNew) {
            $campaign->setActive(true);
            $em->persist($campaign);
        }
        $em->flush();

        $this->addFlash('success', 'Campagne ' . ($isNew ? 'créée' : 'mise à jour') . '.');
        return $this->redirectToRoute('manage_campaign_index');
    }

    #[Route('/campagnes/{id}/basculer', name: 'manage_campaign_toggle', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(Campaign $campaign, CampaignRepository $repository, EntityManagerInterface $em): Response
    {
        $newActiveState = !$campaign->isActive();
        $campaign->setActive($newActiveState);

        // Une seule campagne active à la fois : en activer une désactive automatiquement
        // toutes les autres.
        $deactivatedIds = [];
        if ($newActiveState) {
            foreach ($repository->findAllOrdered() as $other) {
                if ($other->getId() !== $campaign->getId() && $other->isActive()) {
                    $other->setActive(false);
                    $deactivatedIds[] = $other->getId();
                }
            }
        }

        $em->flush();

        return $this->json([
            'ok' => true,
            'active' => $campaign->isActive(),
            'statusLabel' => $campaign->getStatusLabel(),
            'deactivatedIds' => $deactivatedIds,
        ]);
    }

    #[Route('/campagnes/{id}/supprimer', name: 'manage_campaign_remove', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function remove(Campaign $campaign, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($campaign);
        $em->flush();

        $this->addFlash('success', 'Campagne supprimée.');
        return $this->redirectToRoute('manage_campaign_index');
    }
}
