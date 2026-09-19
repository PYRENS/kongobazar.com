<?php

namespace App\Controller\Manage;

use App\Entity\PaymentMethod;
use App\Repository\PaymentMethodRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PaymentMethodController extends AbstractController
{
    #[Route('/parametres/moyens-paiement', name: 'manage_payment_method_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(PaymentMethodRepository $repository): Response
    {
        return $this->render('manage/payment_method/index.html.twig', [
            'methods' => $repository->findAllOrdered(),
        ]);
    }

    #[Route('/parametres/moyens-paiement/ajouter', name: 'manage_payment_method_add', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function add(Request $request, PaymentMethodRepository $repository, EntityManagerInterface $em): RedirectResponse
    {
        $name = trim((string) $request->request->get('name', ''));
        /** @var UploadedFile|null $file */
        $file = $request->files->get('image');

        if ('' === $name || !$file) {
            $this->addFlash('error', 'Le nom et le logo sont obligatoires.');
            return $this->redirectToRoute('manage_payment_method_index');
        }

        $method = new PaymentMethod();
        $method->setName($name);
        $method->setImageFile($file);
        $method->setActive(true);
        $method->setPosition($repository->findNextPosition());

        $em->persist($method);
        $em->flush();

        $this->addFlash('success', 'Moyen de paiement ajouté.');
        return $this->redirectToRoute('manage_payment_method_index');
    }

    #[Route('/parametres/moyens-paiement/{id}/modifier', name: 'manage_payment_method_update', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(PaymentMethod $method, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $name = trim((string) $request->request->get('name', ''));
        if ('' !== $name) {
            $method->setName($name);
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('image');
        if ($file) {
            $method->setImageFile($file);
        }
        $method->setUpdatedAt(new \DateTimeImmutable());

        $em->flush();

        $this->addFlash('success', 'Moyen de paiement mis à jour.');
        return $this->redirectToRoute('manage_payment_method_index');
    }

    #[Route('/parametres/moyens-paiement/{id}/supprimer', name: 'manage_payment_method_remove', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function remove(PaymentMethod $method, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($method);
        $em->flush();

        $this->addFlash('success', 'Moyen de paiement retiré.');
        return $this->redirectToRoute('manage_payment_method_index');
    }

    #[Route('/parametres/moyens-paiement/{id}/basculer', name: 'manage_payment_method_toggle', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(PaymentMethod $method, EntityManagerInterface $em): Response
    {
        $method->setActive(!$method->isActive());
        $em->flush();

        return $this->json(['ok' => true, 'active' => $method->isActive()]);
    }

    #[Route('/parametres/moyens-paiement/{id}/deplacer/{direction}', name: 'manage_payment_method_move', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function move(PaymentMethod $method, string $direction, PaymentMethodRepository $repository, EntityManagerInterface $em): Response
    {
        $methods = $repository->findAllOrdered();
        $index = array_search($method->getId(), array_map(fn ($m) => $m->getId(), $methods), true);
        $swapWith = 'up' === $direction ? $index - 1 : $index + 1;

        if ($swapWith >= 0 && $swapWith < count($methods)) {
            [$methods[$index], $methods[$swapWith]] = [$methods[$swapWith], $methods[$index]];
            foreach ($methods as $i => $m) {
                $m->setPosition($i);
            }
            $em->flush();
        }

        return $this->json(['ok' => true, 'order' => array_map(fn ($m) => $m->getId(), $methods)]);
    }
}
