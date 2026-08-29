<?php

namespace App\Controller\Public;

use App\Entity\ShareEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ShareTrackingController extends AbstractController
{
    /** Beacon appelé en fire-and-forget par le JS de partage — ne doit jamais bloquer/casser le partage lui-même. */
    #[Route('/partage/enregistrer', name: 'public_share_track', host: 'kongobazar.com', methods: ['POST'])]
    public function track(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $entityType = $request->request->get('entity_type') ?: 'static_page';
        $entityId = $request->request->get('entity_id') ? (int) $request->request->get('entity_id') : null;
        $pageKey = $request->request->get('page_key') ?: null;
        $adminLabel = $request->request->get('admin_label') ?: null;
        $platform = $request->request->get('platform') ?: 'native';

        $allowedPlatforms = ['facebook', 'whatsapp', 'x', 'copy_link', 'native'];
        if (!in_array($platform, $allowedPlatforms, true)) {
            return $this->json(['ok' => false], 400);
        }

        $event = new ShareEvent();
        $event->setEntityType($entityType);
        $event->setEntityId($entityId);
        $event->setPageKey($pageKey);
        $event->setAdminLabel($adminLabel ? mb_substr($adminLabel, 0, 200) : null);
        $event->setPlatform($platform);

        $em->persist($event);
        $em->flush();

        return $this->json(['ok' => true]);
    }
}
