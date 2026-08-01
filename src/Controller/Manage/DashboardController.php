<?php

namespace App\Controller\Manage;

use App\Repository\ProductRepository;
use App\Repository\SellerProfileRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'manage_dashboard', host: 'manage.kongobazar.com')]
    public function index(
        UserRepository $userRepository,
        SellerProfileRepository $sellerProfileRepository,
        ProductRepository $productRepository,
    ): Response {
        $totalUsers = $userRepository->countAll();
        $totalSellers = $sellerProfileRepository->countAll();

        $labelMap = [
            'store' => 'Boutiques',
            'pro' => 'Vendeurs Pro',
            'relay' => 'Points Relais',
            'individual' => 'Particuliers vendeurs',
        ];
        $networkBreakdown = [];
        foreach ($sellerProfileRepository->countByTypeBreakdown() as $row) {
            $networkBreakdown[] = ['label' => $labelMap[$row['type']] ?? $row['type'], 'total' => (int) $row['total']];
        }
        $networkBreakdown[] = ['label' => 'Particuliers (acheteurs)', 'total' => max(0, $totalUsers - $totalSellers)];

        return $this->render('manage/dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'totalSellers' => $totalSellers,
            'signupsByMonth' => $userRepository->countByMonth(6),
            'networkBreakdown' => $networkBreakdown,
        ]);
    }
}