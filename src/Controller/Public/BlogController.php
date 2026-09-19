<?php

namespace App\Controller\Public;

use App\Repository\BlogPostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BlogController extends AbstractController
{
    #[Route('/blog', name: 'blog_index', host: 'kongobazar.com')]
    public function index(Request $request, BlogPostRepository $repository): Response
    {
        $term = $request->query->get('q') ?: null;
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 9;

        $total = $repository->countPublished($term);

        return $this->render('public/blog/index.html.twig', [
            'posts' => $repository->findPublishedPaginated($term, $page, $perPage),
            'searchTerm' => $term,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    #[Route('/blog/{slug}', name: 'blog_show', host: 'kongobazar.com')]
    public function show(string $slug, BlogPostRepository $repository): Response
    {
        $post = $repository->findOneBy(['slug' => $slug, 'status' => 'published']);
        if (!$post) {
            throw $this->createNotFoundException();
        }

        return $this->render('public/blog/show.html.twig', [
            'post' => $post,
            'recentPosts' => $repository->findRecentPublished(4),
        ]);
    }
}
