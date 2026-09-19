<?php

namespace App\Controller\Manage;

use App\Entity\BlogPost;
use App\Repository\BlogPostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

class BlogPostManagementController extends AbstractController
{
    #[Route('/blog', name: 'manage_blog_index', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function index(Request $request, BlogPostRepository $repository): Response
    {
        $term = $request->query->get('q') ?: null;
        $status = $request->query->get('status') ?: null;
        $sort = $request->query->get('sort', 'createdAt');
        $dir = $request->query->get('dir', 'DESC');
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 20;

        $total = $repository->countFiltered($term, $status);

        return $this->render('manage/blog/index.html.twig', [
            'posts' => $repository->findFiltered($term, $status, $sort, $dir, $page, $perPage),
            'searchTerm' => $term,
            'currentStatus' => $status,
            'currentSort' => $sort,
            'currentDir' => $dir,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'total' => $total,
        ]);
    }

    #[Route('/blog/ajouter', name: 'manage_blog_new', host: 'manage.kongobazar.com', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('manage/blog/form.html.twig', ['post' => null]);
    }

    #[Route('/blog/{id}/modifier', name: 'manage_blog_edit', host: 'manage.kongobazar.com', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function edit(BlogPost $post): Response
    {
        return $this->render('manage/blog/form.html.twig', ['post' => $post]);
    }

    #[Route('/blog/enregistrer', name: 'manage_blog_create', host: 'manage.kongobazar.com', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $post = new BlogPost();
        return $this->save($post, $request, $em, true);
    }

    #[Route('/blog/{id}/enregistrer', name: 'manage_blog_update', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(BlogPost $post, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        return $this->save($post, $request, $em, false);
    }

    private function save(BlogPost $post, Request $request, EntityManagerInterface $em, bool $isNew): RedirectResponse
    {
        $title = trim((string) $request->request->get('title', ''));
        if ('' === $title) {
            $this->addFlash('error', 'Le titre est obligatoire.');
            return $this->redirectToRoute($isNew ? 'manage_blog_new' : 'manage_blog_edit', $isNew ? [] : ['id' => $post->getId()]);
        }

        $post->setTitle($title);
        $post->setExcerpt($request->request->get('excerpt') ?: null);
        $post->setContent((string) $request->request->get('content', ''));

        $status = $request->request->get('status', 'draft');
        $post->setStatus($status);
        if ('published' === $status && !$post->getPublishedAt()) {
            $post->setPublishedAt(new \DateTimeImmutable());
        }

        if ($isNew) {
            $slugger = new AsciiSlugger();
            $post->setSlug(strtolower((string) $slugger->slug($title)) . '-' . uniqid());
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('cover_image');
        if ($file) {
            $post->setCoverImageFile($file);
        }

        if ($isNew) {
            $em->persist($post);
        }
        $em->flush();

        $this->addFlash('success', 'Article ' . ($isNew ? 'créé' : 'mis à jour') . '.');
        return $this->redirectToRoute('manage_blog_index');
    }

    #[Route('/blog/{id}/supprimer', name: 'manage_blog_remove', host: 'manage.kongobazar.com', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function remove(BlogPost $post, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($post);
        $em->flush();

        $this->addFlash('success', 'Article supprimé.');
        return $this->redirectToRoute('manage_blog_index');
    }
}
