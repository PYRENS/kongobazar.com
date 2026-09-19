<?php

namespace App\Controller\Manage;

use App\Repository\FooterSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FooterSettingController extends AbstractController
{
    #[Route('/parametres/footer', name: 'manage_footer_setting', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(FooterSettingRepository $repository): Response
    {
        return $this->render('manage/footer_setting/index.html.twig', [
            'setting' => $repository->getSingleton(),
        ]);
    }

    #[Route('/parametres/footer', name: 'manage_footer_setting_update', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function update(Request $request, FooterSettingRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $setting = $repository->getSingleton();
        $setting->setCompanyDescription($request->request->get('company_description'));
        $setting->setPhone($request->request->get('phone'));
        $setting->setPhoneAvailability($request->request->get('phone_availability'));
        $setting->setAddress($request->request->get('address'));
        $em->flush();

        $this->addFlash('success', 'Réglages du footer enregistrés.');
        return $this->redirectToRoute('manage_footer_setting');
    }
}
