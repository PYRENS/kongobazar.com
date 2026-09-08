<?php

namespace App\Controller\Manage;

use App\Entity\AdministrativeUnit;
use App\Entity\Notification;
use App\Entity\SellerProfile;
use App\Repository\PreorderSettingRepository;
use App\Repository\SellerProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Attribution directe, par l'admin, du privilège "Prochainement"/"Précommande" par
 * vendeur (boutique ou pro). Pas encore de file de demandes — les vendeurs ne peuvent
 * pas encore soumettre de demande depuis leur espace (prévu plus tard) ; en attendant,
 * cet écran sert à attribuer/retirer le privilège directement.
 */
class SellerPrivilegeController extends AbstractController
{
    private const PRIVILEGE_LABELS = [
        'none' => 'Aucun',
        'coming_soon' => 'Prochainement seul',
        'coming_soon_preorder' => 'Prochainement + Précommande',
    ];

    #[Route('/parametres/privileges-vendeur', name: 'manage_seller_privilege_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(Request $request, SellerProfileRepository $repository, PreorderSettingRepository $preorderSettingRepository, EntityManagerInterface $em): Response
    {
        [$term, $privilege, $status, $locationId, $sort, $dir, $page, $perPage] = $this->extractFilters($request);
        $locationIds = $this->resolveLocationIdsWithDescendants($locationId, $em);

        $total = $repository->countPrivilegeList($term, $privilege, $status, $locationIds);

        $stats = [
            'total' => $repository->countPrivilegeList(null, null, null, null),
            'none' => $repository->countPrivilegeList(null, 'none', null, null),
            'coming_soon' => $repository->countPrivilegeList(null, 'coming_soon', null, null),
            'coming_soon_preorder' => $repository->countPrivilegeList(null, 'coming_soon_preorder', null, null),
        ];

        return $this->render('manage/seller_privilege/index.html.twig', [
            'stats' => $stats,
            'rows' => $repository->findPrivilegeList($term, $privilege, $status, $locationIds, $sort, $dir, $page, $perPage),
            'preorderSetting' => $preorderSettingRepository->getSingleton(),
            'privilegeLabels' => self::PRIVILEGE_LABELS,
            'searchTerm' => $term,
            'currentPrivilege' => $privilege,
            'currentStatus' => $status,
            'currentLocation' => $locationId,
            'currentSort' => $sort,
            'currentDir' => $dir,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'perPage' => $perPage,
            'total' => $total,
        ]);
    }

    #[Route('/parametres/privileges-vendeur/liste-fragment', name: 'manage_seller_privilege_index_fragment', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function indexFragment(Request $request, SellerProfileRepository $repository, EntityManagerInterface $em): Response
    {
        [$term, $privilege, $status, $locationId, $sort, $dir, $page, $perPage] = $this->extractFilters($request);
        $locationIds = $this->resolveLocationIdsWithDescendants($locationId, $em);

        $total = $repository->countPrivilegeList($term, $privilege, $status, $locationIds);
        $pages = max(1, (int) ceil($total / $perPage));

        return $this->json([
            'rowsHtml' => $this->renderView('manage/seller_privilege/_index_rows.html.twig', [
                'rows' => $repository->findPrivilegeList($term, $privilege, $status, $locationIds, $sort, $dir, $page, $perPage),
                'privilegeLabels' => self::PRIVILEGE_LABELS,
            ]),
            'footerInfo' => $total . ' vendeur' . ($total != 1 ? 's' : '') . ' au total — page ' . $page . ' / ' . $pages,
            'paginationHtml' => $this->renderView('manage/seller_privilege/_index_pagination.html.twig', ['page' => $page, 'pages' => $pages]),
        ]);
    }

    private function extractFilters(Request $request): array
    {
        $term = $request->query->get('q') ?: null;
        $privilege = $request->query->get('privilege') ?: null;
        $status = $request->query->get('status') ?: null;
        $locationId = $request->query->get('location') ? (int) $request->query->get('location') : null;
        $sort = $request->query->get('sort', 'displayName');
        $dir = $request->query->get('dir', 'ASC');
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = in_array((int) $request->query->get('perPage', 20), [10, 20, 50, 100], true)
            ? (int) $request->query->get('perPage', 20) : 20;

        return [$term, $privilege, $status, $locationId, $sort, $dir, $page, $perPage];
    }

    private function resolveLocationIdsWithDescendants(?int $locationId, EntityManagerInterface $em): ?array
    {
        if (!$locationId) {
            return null;
        }
        $unit = $em->getRepository(AdministrativeUnit::class)->find($locationId);
        return $unit ? array_map(fn ($u) => $u->getId(), $unit->getDescendantUnits()) : [$locationId];
    }

    #[Route('/parametres/privileges-vendeur/{id}/definir', name: 'manage_seller_privilege_update', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(SellerProfile $seller, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $newPrivilege = (string) $request->request->get('privilege', 'none');
        $seller->setComingSoonPrivilege($newPrivilege);

        $notification = new Notification();
        $notification->setUser($seller->getUser());
        $notification->setChannel('email');
        $notification->setType('seller_privilege_updated');
        $notification->setPayload([
            'privilege' => $newPrivilege,
            'privilegeLabel' => self::PRIVILEGE_LABELS[$newPrivilege] ?? $newPrivilege,
        ]);
        $em->persist($notification);

        $em->flush();

        $this->addFlash('success', 'Privilège mis à jour pour ' . $seller->getDisplayName() . '.');
        return $this->redirectToRoute('manage_seller_privilege_index', $request->query->all());
    }

    #[Route('/parametres/privileges-vendeur/seuil-precommande', name: 'manage_preorder_min_percent_update', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function updateMinPercent(Request $request, PreorderSettingRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $setting = $repository->getSingleton();
        $setting->setMinPercent((int) $request->request->get('min_percent', 30));
        $em->flush();

        $this->addFlash('success', 'Pourcentage minimum de précommande mis à jour.');
        return $this->redirectToRoute('manage_seller_privilege_index');
    }
}
