<?php

namespace App\Controller\Manage;

use App\Entity\HowItWorksSetting;
use App\Repository\HowItWorksSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Section "Comment ça marche" de l'accueil : afficher/masquer + emplacement. */
class HowItWorksSettingController extends AbstractController
{
    public function __construct(
        private readonly HowItWorksSettingRepository $repository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/parametres/comment-ca-marche-accueil', name: 'manage_how_it_works_setting', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('manage/how_it_works_setting/index.html.twig', [
            'setting' => $this->repository->getSingleton(),
            'positions' => HowItWorksSetting::POSITIONS,
        ]);
    }

    #[Route('/parametres/comment-ca-marche-accueil/basculer', name: 'manage_how_it_works_toggle', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function toggleEnabled(): JsonResponse
    {
        $setting = $this->repository->getSingleton();
        $setting->setEnabled(!$setting->isEnabled());
        $this->em->flush();

        return $this->json(['ok' => true, 'enabled' => $setting->isEnabled()]);
    }

    #[Route('/parametres/comment-ca-marche-accueil/emplacement', name: 'manage_how_it_works_position', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function updatePosition(Request $request): JsonResponse
    {
        $position = (string) $request->request->get('position', '');

        if (!isset(HowItWorksSetting::POSITIONS[$position])) {
            return $this->json(['ok' => false, 'message' => 'Emplacement inconnu.'], 400);
        }

        $setting = $this->repository->getSingleton();
        $setting->setPosition($position);
        $this->em->flush();

        return $this->json([
            'ok' => true,
            'position' => $setting->getPosition(),
            'label' => $setting->getPositionLabel(),
            'message' => 'Emplacement enregistré.',
        ]);
    }
}