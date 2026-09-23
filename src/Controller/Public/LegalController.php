<?php

namespace App\Controller\Public;

use App\Entity\LegalAcceptance;
use App\Entity\LegalDocumentVersion;
use App\Entity\User;
use App\Repository\LegalAcceptanceRepository;
use App\Repository\LegalDocumentRepository;
use App\Repository\LegalDocumentVersionRepository;
use App\Service\LegalPdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LegalController extends AbstractController
{
    /** Page bloquante : montre le premier document en attente d'acceptation. */
    #[IsGranted('ROLE_USER')]
    #[Route('/compte/conditions-a-accepter', name: 'public_legal_pending', host: 'kongobazar.com')]
    public function pending(LegalAcceptanceRepository $acceptanceRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $pending = $acceptanceRepository->findPendingVersionsForUser($user, 'public');

        if (!$pending) {
            $target = $this->container->get('request_stack')->getSession()->get('legal_redirect_after_accept');
            return $this->redirect($target ?: $this->generateUrl('public_home'));
        }

        return $this->render('public/legal/pending.html.twig', [
            'version' => $pending[0],
            'remainingCount' => count($pending),
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/compte/conditions-a-accepter/{id}/accepter', name: 'public_legal_accept', host: 'kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function accept(int $id, Request $request, LegalDocumentVersionRepository $versionRepository, EntityManagerInterface $em): RedirectResponse
    {
        $version = $versionRepository->find($id);
        if (!$version) {
            throw $this->createNotFoundException();
        }

        $acceptance = (new LegalAcceptance())
            ->setUser($this->getUser())
            ->setVersion($version)
            ->setIpAddress($request->getClientIp());

        $em->persist($acceptance);
        $em->flush();

        return $this->redirectToRoute('public_legal_pending');
    }

    /** Consultation publique : sélecteur de version, bascule en direct côté navigateur. */
    #[Route('/legal/{code}', name: 'public_legal_document_show', host: 'kongobazar.com')]
    public function show(string $code, LegalDocumentRepository $documentRepository, LegalDocumentVersionRepository $versionRepository): Response
    {
        $document = $documentRepository->findByCode($code);
        if (!$document) {
            throw $this->createNotFoundException();
        }

        $viewable = array_values(array_filter(
            $versionRepository->findForDocument($document),
            fn (LegalDocumentVersion $v) => $v->isPublished() || 'archived' === $v->getStatus()
        ));
        if (!$viewable) {
            throw $this->createNotFoundException('Aucune version publiée.');
        }

        $currentVersion = $viewable[0]; // la plus récente (déjà triées par version décroissante)

        $versionsData = [];
        foreach ($viewable as $v) {
            $articlesHtml = '';
            foreach ($v->getArticles() as $article) {
                $articlesHtml .= '<div class="legal-article"><strong>' . htmlspecialchars($article->getTitle()) . '</strong><div class="mt-1">' . $article->getBody() . '</div></div>';
            }
            $versionsData[] = [
                'id' => $v->getId(),
                'label' => 'Version ' . $v->getVersionNumber() . ($v->isPublished() ? ' — en vigueur' : '') . ($v->getPublishedAt() ? ' (' . $v->getPublishedAt()->format('d/m/Y') . ')' : ''),
                'subtitle' => 'Version ' . $v->getVersionNumber() . ($v->getPublishedAt() ? ', en vigueur depuis le ' . $v->getPublishedAt()->format('d/m/Y') : ''),
                'articlesHtml' => $articlesHtml,
            ];
        }

        return $this->render('public/legal/show.html.twig', [
            'document' => $document,
            'version' => $currentVersion,
            'versionsData' => $versionsData,
            'prefillEmail' => $this->getUser()?->getUserIdentifier(),
        ]);
    }

    /** Téléchargement du PDF ; ?v={id} pour une version précise, sinon la plus récente consultable. */
    #[Route('/legal/{code}/pdf', name: 'public_legal_document_pdf', host: 'kongobazar.com')]
    public function pdf(string $code, Request $request, LegalDocumentRepository $documentRepository, LegalDocumentVersionRepository $versionRepository, LegalPdfGenerator $pdfGenerator): Response
    {
        $document = $documentRepository->findByCode($code);
        if (!$document) {
            throw $this->createNotFoundException();
        }

        $version = $this->resolveViewableVersion($document, $request->query->get('v'), $versionRepository);
        if (!$version) {
            throw $this->createNotFoundException();
        }

        $pdf = $pdfGenerator->generate($document, $version);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $document->getCode() . '-v' . $version->getVersionNumber() . '.pdf"',
        ]);
    }

    /** Envoie par email la version choisie (champ caché du formulaire), sinon la plus récente consultable. */
    #[Route('/legal/{code}/envoyer', name: 'public_legal_document_send', host: 'kongobazar.com', methods: ['POST'])]
    public function send(
        string $code,
        Request $request,
        LegalDocumentRepository $documentRepository,
        LegalDocumentVersionRepository $versionRepository,
        LegalPdfGenerator $pdfGenerator,
        \Symfony\Component\Mailer\MailerInterface $mailer,
        ValidatorInterface $validator,
    ): RedirectResponse {
        $document = $documentRepository->findByCode($code);
        if (!$document) {
            throw $this->createNotFoundException();
        }

        $version = $this->resolveViewableVersion($document, $request->request->get('version_id'), $versionRepository);
        if (!$version) {
            throw $this->createNotFoundException();
        }

        $email = trim((string) $request->request->get('email', ''));
        $errors = $validator->validate($email, [new EmailConstraint()]);

        if ('' === $email || count($errors) > 0) {
            $this->addFlash('legal_send_error', 'Adresse e-mail invalide.');
            return $this->redirectToRoute('public_legal_document_show', ['code' => $code]);
        }

        $pdf = $pdfGenerator->generate($document, $version);

        $message = (new Email())
            ->to($email)
            ->subject('KongoBazar — ' . $document->getLabel())
            ->text('Vous trouverez ci-joint le document "' . $document->getLabel() . '" (version ' . $version->getVersionNumber() . ') de KongoBazar.')
            ->attach($pdf, $document->getCode() . '-v' . $version->getVersionNumber() . '.pdf', 'application/pdf');

        try {
            $mailer->send($message);
            $this->addFlash('legal_send_success', 'Le document a été envoyé à ' . $email . '.');
        } catch (\Throwable $e) {
            $this->addFlash('legal_send_error', 'L\'envoi a échoué. Réessaie plus tard.');
        }

        return $this->redirectToRoute('public_legal_document_show', ['code' => $code]);
    }

    /** Résout une version demandée par id : doit appartenir au document ET être publiée ou archivée (jamais un brouillon). */
    private function resolveViewableVersion(\App\Entity\LegalDocument $document, mixed $requestedId, LegalDocumentVersionRepository $versionRepository): ?LegalDocumentVersion
    {
        if ($requestedId) {
            $version = $versionRepository->find((int) $requestedId);
            if ($version && $version->getDocument()->getId() === $document->getId() && !$version->isDraft()) {
                return $version;
            }
        }

        return $versionRepository->findLatestPublished($document);
    }
}