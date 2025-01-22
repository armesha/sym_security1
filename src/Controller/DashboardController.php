<?php

namespace App\Controller;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $posts = $entityManager->getRepository(Post::class)->findAll();
        
        return $this->render('dashboard/index.html.twig', [
            'posts' => $posts,
            'user' => $this->getUser()
        ]);
    }

    #[Route('/post/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function newPost(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $post = new Post();
            $post->setTitle($request->request->get('title'));
            $post->setContent($request->request->get('content'));
            $post->setAuthor($this->getUser());

            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Post created successfully!');
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('dashboard/new_post.html.twig');
    }

    #[Route('/post/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    public function editPost(Post $post, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('POST_EDIT', $post);

        if ($request->isMethod('POST')) {
            $post->setTitle($request->request->get('title'));
            $post->setContent($request->request->get('content'));

            $entityManager->flush();

            $this->addFlash('success', 'Post updated successfully!');
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('dashboard/edit_post.html.twig', [
            'post' => $post
        ]);
    }
}
