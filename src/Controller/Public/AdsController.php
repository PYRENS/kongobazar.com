<?php

namespace App\Controller\Public;

use App\Repository\AdvertisementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class AdsController extends AbstractController
{
    #[Route('/ads/{id}/click/{zoneKey}', name: 'ads_click', host: 'kongobazar.com')]
    public function click(int $id, string $zoneKey, AdvertisementRepository $advertisementRepository, \App\Repository\AdvertisementZonePlacementRepository $placementRepository, EntityManagerInterface $em): RedirectResponse
    {
        $ad = $advertisementRepository->find($id);

        if (null === $ad) {
            return $this->redirectToRoute('public_home');
        }

        $placement = $placementRepository->findOneByAdvertisementAndZone($id, $zoneKey);
        if ($placement) {
            $placement->incrementClickCount();
            $em->flush();
        }

        return new RedirectResponse($ad->getTargetUrl() ?: $this->generateUrl('public_home'));
    }
}