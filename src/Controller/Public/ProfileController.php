<?php

namespace App\Controller\Public;

use App\Entity\User;
use App\Repository\AdministrativeUnitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/compte/completer-profil', name: 'public_complete_profile', host: 'kongobazar.com')]
    public function complete(
        Request $request,
        AdministrativeUnitRepository $administrativeUnitRepository,
        EntityManagerInterface $em,
        \App\Repository\LoginBackgroundSectionSettingRepository $sectionSettingRepository,
        \App\Repository\LoginBackgroundImageRepository $imageRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Obligatoire uniquement quand on vient du tunnel d'achat (aucun bouton "Plus tard" dans ce cas)
        $required = $request->query->getBoolean('required') || 1 === $request->query->getInt('required', 0);

        if ($request->isMethod('POST')) {
            $civility = (string) $request->request->get('civility', '');
            $dob = (string) $request->request->get('date_of_birth', '');
            $phone = trim((string) $request->request->get('phone', ''));
            $unitId = $request->request->get('administrative_unit');
            $address = trim((string) $request->request->get('address', ''));

            $user->setCivility($civility ?: null);
            if ($dob) {
                try {
                    $user->setDateOfBirth(new \DateTimeImmutable($dob));
                } catch (\Exception) {
                    // date invalide : ignorée, le champ reste vide
                }
            }
            $user->setPhone($phone ?: null);
            $user->setAddress($address ?: null);
            $user->setAcceptsNewsletter($request->request->getBoolean('accepts_newsletter'));

            if ($unitId) {
                $unit = $administrativeUnitRepository->find((int) $unitId);
                if ($unit) {
                    $user->setAdministrativeUnit($unit);
                }
            }

            /** @var UploadedFile|null $photo */
            $photo = $request->files->get('avatar');
            if ($photo && $photo->isValid()) {
                $user->setAvatarFile($photo);
            }

            $em->flush();

            if ($required && !$user->isProfileComplete()) {
                $this->addFlash('error', 'Merci de compléter tous les champs obligatoires pour continuer ta commande.');
                return $this->redirectToRoute('public_complete_profile', ['required' => 1]);
            }

            $this->addFlash('success', 'Profil mis à jour.');
            return $this->redirectToRoute($required ? 'checkout_index' : 'public_home');
        }

        $sectionEnabled = $sectionSettingRepository->getSingleton()->isEnabled();

        return $this->render('public/security/complete_profile.html.twig', [
            'user' => $user,
            'required' => $required,
            'wasProfileComplete' => $user->isProfileComplete(),
            'rootUnits' => $administrativeUnitRepository->findActiveRootUnits(),
            'loginImage' => $sectionEnabled ? $imageRepository->pickActiveRandom('complete_profile') : null,
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/compte/completer-profil/plus-tard', name: 'public_complete_profile_later', host: 'kongobazar.com')]
    public function later(): RedirectResponse
    {
        return $this->redirectToRoute('public_home');
    }
}