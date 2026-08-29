<?php

namespace App\Controller\Manage;

use App\Repository\SiteIdentitySettingRepository;
use App\Service\FaviconGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SiteIdentityController extends AbstractController
{
    #[Route('/parametres/identite-visuelle', name: 'manage_site_identity', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(SiteIdentitySettingRepository $repository): Response
    {
        return $this->render('manage/site_identity/index.html.twig', [
            'setting' => $repository->getSingleton(),
        ]);
    }

    #[Route('/parametres/identite-visuelle', name: 'manage_site_identity_update', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function update(
        Request $request,
        SiteIdentitySettingRepository $repository,
        EntityManagerInterface $em,
        FaviconGenerator $generator,
    ): Response {
        $setting = $repository->getSingleton();
        $themeColor = $request->request->get('theme_color') ?: $setting->getThemeColor();
        $setting->setThemeColor($themeColor);

        $file = $request->files->get('source_image_file');
        if ($file) {
            try {
                // Génère d'abord depuis le fichier temporaire brut, AVANT que Vich ne le déplace via flush().
                $generator->generateFromPath($file->getPathname(), $themeColor);

                $setting->setSourceImageFile($file);
                $setting->setGeneratedAt(new \DateTimeImmutable());
                $em->persist($setting);
                $em->flush();

                $this->addFlash('success', 'Favicon et icônes régénérés avec succès.');
            } catch (\RuntimeException $e) {
                $this->addFlash('error', 'Génération échouée : ' . $e->getMessage());
            }
        } else {
            $em->flush();
            $this->addFlash('success', 'Couleur de thème mise à jour.');
        }

        return $this->redirectToRoute('manage_site_identity');
    }
}
