<?php

namespace App\Controller\Public;

use App\Repository\AdministrativeUnitRepository;
use App\Repository\SellerProfileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DirectoryController extends AbstractController
{
    #[Route('/notre-reseau', name: 'network_index', host: 'kongobazar.com')]
    public function index(
        Request $request,
        SellerProfileRepository $sellerProfileRepository,
        AdministrativeUnitRepository $administrativeUnitRepository,
        \App\Repository\UserRepository $userRepository,
    ): Response {
        $term = trim((string) $request->query->get('q', ''));
        $type = $request->query->get('type') ?: null;
        $locationId = $request->query->get('location');

        $location = $locationId ? $administrativeUnitRepository->find($locationId) : null;

        $results = $sellerProfileRepository->searchDirectory(
            $term ?: null,
            $type,
            $location
        );

        $resultsWithType = array_map(function ($profile) {
            return [
                'profile' => $profile,
                'typeLabel' => match (true) {
                    $profile instanceof \App\Entity\StoreProfile => 'Boutique',
                    $profile instanceof \App\Entity\ProProfile => 'Vendeur Pro',
                    $profile instanceof \App\Entity\IndividualProfile => 'Particulier',
                    default => 'Point Relais',
                },
            ];
        }, $results);

        // Pour l'instant, seul le niveau 1 (provinces) est importé
        $locations = $administrativeUnitRepository->findActiveRootUnits();

        $totalUsers = $userRepository->countAll();
        $totalSellers = $sellerProfileRepository->countAll();

        $networkStats = [
            'particuliers' => max(0, $totalUsers - $totalSellers),
            'pro' => $sellerProfileRepository->countByType('pro'),
            'store' => $sellerProfileRepository->countByType('store'),
            'relay' => $sellerProfileRepository->countByType('relay'),
        ];

        return $this->render('public/directory.html.twig', [
            'results' => $resultsWithType,
            'locations' => $locations,
            'currentTerm' => $term,
            'currentType' => $type,
            'currentLocationId' => $locationId,
            'networkStats' => $networkStats,
            'breadcrumbs' => [
                ['label' => 'Notre Réseau', 'url' => null],
            ],
        ]);
    }
}