<?php

namespace App\Controller\Manage;

use App\Entity\LoginBackgroundImage;
use App\Repository\LoginBackgroundImageRepository;
use App\Repository\LoginBackgroundSectionSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LoginBackgroundController extends AbstractController
{
    #[Route('/parametres/connexion-images', name: 'manage_login_background_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(LoginBackgroundImageRepository $repository, LoginBackgroundSectionSettingRepository $settingRepository): Response
    {
        return $this->render('manage/login_background/index.html.twig', [
            'images' => $repository->findAllOrdered(),
            'setting' => $settingRepository->getSingleton(),
        ]);
    }

    #[Route('/parametres/connexion-images/ajouter', name: 'manage_login_background_new', host: 'manage.kongobazar.com', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        return $this->handleForm(new LoginBackgroundImage(), $request, $em, true);
    }

    #[Route('/parametres/connexion-images/{id}/modifier', name: 'manage_login_background_edit', host: 'manage.kongobazar.com', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(LoginBackgroundImage $image, Request $request, EntityManagerInterface $em): Response
    {
        return $this->handleForm($image, $request, $em, false);
    }

    #[Route('/parametres/connexion-images/{id}/supprimer', name: 'manage_login_background_delete', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(LoginBackgroundImage $image, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($image);
        $em->flush();

        $this->addFlash('success', 'Image supprimée.');
        return $this->redirectToRoute('manage_login_background_index');
    }

    #[Route('/parametres/connexion-images/{id}/basculer', name: 'manage_login_background_toggle', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(LoginBackgroundImage $image, EntityManagerInterface $em): Response
    {
        $image->setActive(!$image->isActive());
        $em->flush();

        return $this->json(['ok' => true, 'active' => $image->isActive()]);
    }

    /** Interrupteur général : active/désactive tout le module (repli sur le logo si coupé). */
    #[Route('/parametres/connexion-images/section/basculer', name: 'manage_login_background_toggle_section', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function toggleSection(LoginBackgroundSectionSettingRepository $settingRepository, EntityManagerInterface $em): Response
    {
        $setting = $settingRepository->getSingleton();
        $setting->setEnabled(!$setting->isEnabled());
        $em->flush();

        return $this->json(['ok' => true, 'enabled' => $setting->isEnabled()]);
    }

    private function handleForm(LoginBackgroundImage $image, Request $request, EntityManagerInterface $em, bool $isNew): Response
    {
        if ($request->isMethod('POST')) {
            $title = trim((string) $request->request->get('title', ''));
            /** @var UploadedFile|null $file */
            $file = $request->files->get('image');

            if ($isNew && !$file) {
                $this->addFlash('error', 'Une image est obligatoire.');
                return $this->render('manage/login_background/form.html.twig', ['image' => $image]);
            }

            $image->setTitle('' !== $title ? $title : null);
            $image->setActive($request->request->getBoolean('active', true));
            if ($file) {
                $image->setImageFile($file);
            }

            if ($isNew) {
                $em->persist($image);
            }
            $em->flush();

            $this->addFlash('success', $isNew ? 'Image ajoutée.' : 'Image mise à jour.');
            return $this->redirectToRoute('manage_login_background_index');
        }

        return $this->render('manage/login_background/form.html.twig', ['image' => $isNew ? null : $image]);
    }
}