<?php

namespace App\Controller\Manage;

use App\Entity\LegalArticle;
use App\Entity\LegalDocument;
use App\Entity\LegalDocumentVersion;
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
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LegalDocumentController extends AbstractController
{
    #[Route('/legal', name: 'manage_legal_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(LegalDocumentRepository $repository, LegalDocumentVersionRepository $versionRepository): Response
    {
        $documents = $repository->findAllOrdered();
        $latestByDocument = [];
        $draftByDocument = [];
        foreach ($documents as $document) {
            $latestByDocument[$document->getId()] = $versionRepository->findLatestPublished($document);
            $draftByDocument[$document->getId()] = $versionRepository->findDraft($document);
        }

        return $this->render('manage/legal/index.html.twig', [
            'documents' => $documents,
            'latestByDocument' => $latestByDocument,
            'draftByDocument' => $draftByDocument,
        ]);
    }

    #[Route('/legal/nouveau', name: 'manage_legal_document_new', host: 'manage.kongobazar.com', methods: ['GET', 'POST'])]
    public function newDocument(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $code = trim((string) $request->request->get('code', ''));
            $label = trim((string) $request->request->get('label', ''));

            if ('' === $code || '' === $label || !preg_match('/^[a-z0-9_-]+$/', $code)) {
                $this->addFlash('error', 'Code (lettres minuscules, chiffres, - et _ uniquement) et libellé obligatoires.');
                return $this->render('manage/legal/document_form.html.twig');
            }

            $document = (new LegalDocument())
                ->setCode($code)
                ->setLabel($label)
                ->setTargetSpace((string) $request->request->get('target_space', 'public'))
                ->setRequiresAcceptance($request->request->getBoolean('requires_acceptance', true));

            $em->persist($document);
            $em->flush();

            $this->addFlash('success', 'Document créé. Crée maintenant sa première version.');
            return $this->redirectToRoute('manage_legal_index');
        }

        return $this->render('manage/legal/document_form.html.twig');
    }

    /** Historique complet des versions d'un document, les plus récentes en premier. */
    #[Route('/legal/{id}/historique', name: 'manage_legal_document_history', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function history(LegalDocument $document, LegalDocumentVersionRepository $versionRepository): Response
    {
        return $this->render('manage/legal/history.html.twig', [
            'document' => $document,
            'versions' => $versionRepository->findForDocument($document),
        ]);
    }

    /** Crée une nouvelle version brouillon, en copiant les articles de la dernière version publiée. */
    #[Route('/legal/{id}/versions/nouvelle', name: 'manage_legal_version_new', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function newVersion(LegalDocument $document, LegalDocumentVersionRepository $versionRepository, EntityManagerInterface $em): RedirectResponse
    {
        $existingDraft = $versionRepository->findDraft($document);
        if ($existingDraft) {
            return $this->redirectToRoute('manage_legal_version_edit', ['id' => $existingDraft->getId()]);
        }

        $version = (new LegalDocumentVersion())
            ->setDocument($document)
            ->setVersionNumber($versionRepository->getNextVersionNumber($document))
            ->setStatus(LegalDocumentVersion::STATUS_DRAFT);

        $latestPublished = $versionRepository->findLatestPublished($document);
        if ($latestPublished) {
            foreach ($latestPublished->getArticles() as $article) {
                $version->addArticle(
                    (new LegalArticle())
                        ->setPosition($article->getPosition())
                        ->setTitle($article->getTitle())
                        ->setBody($article->getBody())
                        ->setChangeType(LegalArticle::CHANGE_UNCHANGED)
                );
            }
        }

        $em->persist($version);
        $em->flush();

        return $this->redirectToRoute('manage_legal_version_edit', ['id' => $version->getId()]);
    }

    #[Route('/legal/versions/{id}/modifier', name: 'manage_legal_version_edit', host: 'manage.kongobazar.com', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function editVersion(LegalDocumentVersion $version, Request $request, EntityManagerInterface $em): Response
    {
        if (!$version->isDraft()) {
            // Version publiée ou archivée : consultation en lecture seule, aucune modification possible.
            return $this->render('manage/legal/version_form.html.twig', ['version' => $version, 'readOnly' => true]);
        }

        if ($request->isMethod('POST')) {
            $version->setChangeSummary(trim((string) $request->request->get('change_summary', '')) ?: null);

            $version->getArticles()->clear();
            $position = 0;
            $titles = $request->request->all('article_title');
            $bodies = $request->request->all('article_body');
            $changeTypes = $request->request->all('article_change_type');

            foreach ($titles as $i => $title) {
                $title = trim((string) $title);
                $body = trim((string) ($bodies[$i] ?? ''));
                if ('' === $title && '' === $body) {
                    continue;
                }
                $version->addArticle(
                    (new LegalArticle())
                        ->setPosition($position++)
                        ->setTitle($title)
                        ->setBody($body)
                        ->setChangeType((string) ($changeTypes[$i] ?? LegalArticle::CHANGE_UNCHANGED))
                );
            }

            $em->flush();
            $this->addFlash('success', 'Version enregistrée.');
            return $this->redirectToRoute('manage_legal_version_edit', ['id' => $version->getId()]);
        }

        return $this->render('manage/legal/version_form.html.twig', ['version' => $version]);
    }

    /** Téléchargement du PDF d'une version, publiée ou non. */
    #[Route('/legal/versions/{id}/pdf', name: 'manage_legal_version_pdf', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function pdfVersion(LegalDocumentVersion $version, LegalPdfGenerator $pdfGenerator): Response
    {
        $pdf = $pdfGenerator->generate($version->getDocument(), $version);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $version->getDocument()->getCode() . '-v' . $version->getVersionNumber() . '.pdf"',
        ]);
    }

    /** Envoi par email d'une version, publiée ou non, depuis l'admin. */
    #[Route('/legal/versions/{id}/envoyer', name: 'manage_legal_version_send', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function sendVersion(
        LegalDocumentVersion $version,
        Request $request,
        LegalPdfGenerator $pdfGenerator,
        \Symfony\Component\Mailer\MailerInterface $mailer,
        ValidatorInterface $validator,
    ): RedirectResponse {
        $email = trim((string) $request->request->get('email', ''));
        $errors = $validator->validate($email, [new EmailConstraint()]);

        if ('' === $email || count($errors) > 0) {
            $this->addFlash('error', 'Adresse e-mail invalide.');
            return $this->redirectToRoute('manage_legal_version_edit', ['id' => $version->getId()]);
        }

        $document = $version->getDocument();
        $pdf = $pdfGenerator->generate($document, $version);

        $message = (new Email())
            ->to($email)
            ->subject('KongoBazar — ' . $document->getLabel() . ' (v' . $version->getVersionNumber() . ')')
            ->text('Document légal "' . $document->getLabel() . '", version ' . $version->getVersionNumber() . '.')
            ->attach($pdf, $document->getCode() . '-v' . $version->getVersionNumber() . '.pdf', 'application/pdf');

        try {
            $mailer->send($message);
            $this->addFlash('success', 'Document envoyé à ' . $email . '.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'L\'envoi a échoué. Réessaie plus tard.');
        }

        return $this->redirectToRoute('manage_legal_version_edit', ['id' => $version->getId()]);
    }

    #[Route('/legal/versions/{id}/publier', name: 'manage_legal_version_publish', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function publishVersion(LegalDocumentVersion $version, LegalDocumentVersionRepository $versionRepository, EntityManagerInterface $em): RedirectResponse
    {
        if (!$version->isDraft()) {
            $this->addFlash('error', 'Cette version n\'est pas un brouillon.');
            return $this->redirectToRoute('manage_legal_index');
        }
        if (0 === count($version->getArticles())) {
            $this->addFlash('error', 'Ajoute au moins un article avant de publier.');
            return $this->redirectToRoute('manage_legal_version_edit', ['id' => $version->getId()]);
        }

        $previous = $versionRepository->findLatestPublished($version->getDocument());
        if ($previous) {
            $previous->setStatus(LegalDocumentVersion::STATUS_ARCHIVED);
        }

        $version->setStatus(LegalDocumentVersion::STATUS_PUBLISHED);
        $version->setPublishedAt(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', 'Version publiée. Les clients concernés devront l\'accepter à leur prochaine connexion.');
        return $this->redirectToRoute('manage_legal_index');
    }
}