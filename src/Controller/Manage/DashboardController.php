<?php

namespace App\Controller\Manage;

use App\Repository\DiscountCampaignRepository;
use App\Repository\OrderItemRepository;
use App\Repository\ProductRepository;
use App\Repository\SellerProfileRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
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
        DiscountCampaignRepository $discountCampaignRepository,
        OrderItemRepository $orderItemRepository,
        EntityManagerInterface $em,
    ): Response {
        $totalUsers = $userRepository->countAll();
        $totalSellers = $sellerProfileRepository->countAll();
        $totalProducts = $productRepository->countFiltered(null, null, null, null);
        $activeFlashSales = $discountCampaignRepository->countByStatus('active');

        $latestProduct = $productRepository->findOneBy([], ['createdAt' => 'DESC']);
        $bestSeller = $productRepository->findOneBy([], ['salesCount' => 'DESC']);
        $latestFlashCampaign = $discountCampaignRepository->findOneBy([], ['id' => 'DESC']);
        $latestOrderItem = $orderItemRepository->findOneBy([], ['id' => 'DESC']);
        $latestSoldProduct = $productRepository->findOneBy(['status' => 'sold'], ['id' => 'DESC']);
        $latestDiscountedProduct = $em->createQueryBuilder()
            ->select('p')->from(\App\Entity\Product::class, 'p')
            ->andWhere('p.compareAtPrice IS NOT NULL')
            ->andWhere('p.compareAtPrice > p.basePrice')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

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
            'totalProducts' => $totalProducts,
            'activeFlashSales' => $activeFlashSales,
            'signupsByMonth' => $userRepository->countByMonth(6),
            'networkBreakdown' => $networkBreakdown,
            'latestProduct' => $latestProduct,
            'bestSeller' => $bestSeller,
            'latestFlashCampaign' => $latestFlashCampaign,
            'latestOrderItem' => $latestOrderItem,
            'latestSoldProduct' => $latestSoldProduct,
            'latestDiscountedProduct' => $latestDiscountedProduct,
        ]);
    }
}