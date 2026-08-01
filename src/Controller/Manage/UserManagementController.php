<?php

namespace App\Controller\Manage;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\AdministrativeUnitRepository;
use App\Repository\LoginHistoryRepository;
use App\Repository\ProductRepository;
use App\Repository\ReviewRepository;
use App\Repository\SellerProfileRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserManagementController extends AbstractController
{
    #[Route('/utilisateurs', name: 'manage_users_index', host: 'manage.kongobazar.com')]
    public function index(
        Request $request,
        UserRepository $userRepository,
        SellerProfileRepository $sellerProfileRepository,
        ReviewRepository $reviewRepository,
        \App\Repository\AdministrativeUnitRepository $administrativeUnitRepository,
    ): Response {
        $term = $request->query->get('q');
        $sortField = $request->query->get('sort', 'id');
        $sortDir = $request->query->get('dir', 'DESC');
        $filterType = $request->query->get('type');
        $filterMinRating = $request->query->get('minRating');
        $filterUnitId = $request->query->get('unit') ? (int) $request->query->get('unit') : null;

        $users = $userRepository->search($term, $sortField, $sortDir);

        $rows = array_map(function (User $user) use ($sellerProfileRepository, $reviewRepository) {
            $profile = $sellerProfileRepository->findOneByUser($user);
            $unit = $user->getAdministrativeUnit();

            return [
                'user' => $user,
                'typeLabel' => $profile ? $sellerProfileRepository->getTypeLabel($profile) : 'Particulier',
                'rating' => $profile ? $reviewRepository->getAverageRatingForSeller($profile) : null,
                'localisation' => $unit ? implode('/', $unit->getLocalisationParts(false)) : null,
                'unitId' => $unit?->getId(),
            ];
        }, $users);

        // Filtres appliqués en mémoire — acceptable au volume actuel,
        // à revoir en base (requêtes dédiées) si la table grossit significativement.
        if ($filterType) {
            $rows = array_filter($rows, fn ($r) => $r['typeLabel'] === $filterType);
        }
        if ($filterMinRating) {
            $rows = array_filter($rows, fn ($r) => $r['rating'] !== null && $r['rating'] >= (float) $filterMinRating);
        }
        if ($filterUnitId) {
            $filterUnit = $administrativeUnitRepository->find($filterUnitId);
            if ($filterUnit) {
                $descendantIds = array_map(fn ($u) => $u->getId(), $filterUnit->getDescendantUnits());
                $rows = array_filter($rows, fn ($r) => $r['unitId'] !== null && in_array($r['unitId'], $descendantIds, true));
            }
        }

        return $this->render('manage/users/index.html.twig', [
            'rows' => array_values($rows),
            'currentTerm' => $term,
            'currentSort' => $sortField,
            'currentDir' => $sortDir,
            'currentType' => $filterType,
            'currentMinRating' => $filterMinRating,
            'currentUnit' => $filterUnitId,
            'provinces' => $administrativeUnitRepository->findActiveRootUnits(),
        ]);
    }

    #[Route('/utilisateurs/{id}', name: 'manage_users_show', host: 'manage.kongobazar.com')]
    public function show(
        User $user,
        SellerProfileRepository $sellerProfileRepository,
        LoginHistoryRepository $loginHistoryRepository,
        \App\Repository\AdministrativeUnitRepository $administrativeUnitRepository,
    ): Response {
        $sellerProfile = $sellerProfileRepository->findOneByUser($user);

        $localisation = $user->getAdministrativeUnit()
            ? implode('/', $user->getAdministrativeUnit()->getLocalisationParts(true))
            : null;

        return $this->render('manage/users/show.html.twig', [
            'targetUser' => $user,
            'sellerProfile' => $sellerProfile,
            'sellerTypeLabel' => $sellerProfile ? $sellerProfileRepository->getTypeLabel($sellerProfile) : 'Particulier',
            'localisation' => $localisation,
            'loginHistory' => $loginHistoryRepository->findBy(['user' => $user], ['loggedInAt' => 'DESC'], 10),
            'root_categories_geo' => $administrativeUnitRepository->findActiveRootUnits(),
        ]);
    }

    #[Route('/utilisateurs/{id}/modifier', name: 'manage_users_update', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function update(User $user, Request $request, \App\Repository\AdministrativeUnitRepository $administrativeUnitRepository, EntityManagerInterface $em): RedirectResponse
    {
        $user->setFirstName($request->request->get('first_name') ?: null);
        $user->setLastName($request->request->get('last_name') ?: null);
        $user->setPhone($request->request->get('phone') ?: null);
        $user->setAddress($request->request->get('address') ?: null);

        $unitId = $request->request->get('administrative_unit');
        $user->setAdministrativeUnit($unitId ? $administrativeUnitRepository->find($unitId) : null);

        $em->flush();

        $this->addFlash('success', 'Informations mises à jour.');
        return $this->redirectToRoute('manage_users_show', ['id' => $user->getId()]);
    }

    #[Route('/utilisateurs/{id}/desactiver', name: 'manage_users_toggle_disable', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function toggleDisable(User $user, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $user->setStatus($user->getStatus() === 'suspended' ? 'active' : 'suspended');
        $em->flush();

        $this->addFlash('success', $user->getEmail() . ' — statut mis à jour : ' . $user->getStatus());

        // Retourne à la page d'où vient le clic (liste OU fiche détail), plutôt que toujours vers la liste
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }
        return $this->redirectToRoute('manage_users_index');
    }

    #[Route('/utilisateurs/{id}/bannir', name: 'manage_users_ban', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function ban(User $user, EntityManagerInterface $em): RedirectResponse
    {
        $user->setStatus($user->getStatus() === 'banned' ? 'active' : 'banned');
        $em->flush();

        $this->addFlash('warning', $user->getEmail() . ' — statut mis à jour : ' . $user->getStatus());
        return $this->redirectToRoute('manage_users_show', ['id' => $user->getId()]);
    }

    #[Route('/utilisateurs/{id}/message', name: 'manage_users_message', host: 'manage.kongobazar.com')]
    public function message(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $notification = new Notification();
            $notification->setUser($user);
            $notification->setChannel('email');
            $notification->setType('admin_message');
            $notification->setPayload([
                'subject' => $request->request->get('subject', ''),
                'body' => $request->request->get('body', ''),
            ]);
            $em->persist($notification);
            $em->flush();

            $this->addFlash('success', 'Message mis en file d\'envoi pour ' . $user->getEmail());
            return $this->redirectToRoute('manage_users_index');
        }

        return $this->render('manage/users/message.html.twig', ['targetUser' => $user]);
    }

    #[Route('/utilisateurs/{id}/activite', name: 'manage_users_activity', host: 'manage.kongobazar.com')]
    public function activity(User $user, SellerProfileRepository $sellerProfileRepository, ReviewRepository $reviewRepository, ProductRepository $productRepository): Response
    {
        $sellerProfile = $sellerProfileRepository->findOneByUser($user);

        return $this->render('manage/users/activity.html.twig', [
            'targetUser' => $user,
            'sellerProfile' => $sellerProfile,
            'averageRating' => $sellerProfile ? $reviewRepository->getAverageRatingForSeller($sellerProfile) : null,
            'reviews' => $sellerProfile ? $reviewRepository->findBy(['target' => $user], ['id' => 'DESC'], 10) : [],
            'products' => $sellerProfile ? $productRepository->findBy(['sellerProfile' => $sellerProfile], ['createdAt' => 'DESC'], 10) : [],
        ]);
    }
}