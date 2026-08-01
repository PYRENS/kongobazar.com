<?php

namespace App\Controller\Public;

use App\Repository\AdvertisementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class AdsController extends AbstractController
{
    #[Route('/ads/{id}/click', name: 'ads_click', host: 'kongobazar.com')]
    public function click(int $id, AdvertisementRepository $advertisementRepository, EntityManagerInterface $em): RedirectResponse
    {
        $ad = $advertisementRepository->find($id);

        if (null === $ad) {
            return $this->redirectToRoute('public_home');
        }

        $ad->incrementClickCount();
        $em->flush();

        return new RedirectResponse($ad->getTargetUrl() ?: $this->generateUrl('public_home'));
    }
}