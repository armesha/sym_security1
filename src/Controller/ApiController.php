<?php

namespace App\Controller;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class ApiController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    #[Route('/posts', name: 'api_posts_list', methods: ['GET'])]
    #[IsGranted('ROLE_API_USER')]
    public function listPosts(EntityManagerInterface $entityManager): JsonResponse
    {
        $this->logger->info('Listing posts');
        $posts = $entityManager->getRepository(Post::class)->findAll();
        
        $data = array_map(function(Post $post) {
            return [
                'id' => $post->getId(),
                'title' => $post->getTitle(),
                'content' => $post->getContent(),
                'author' => $post->getAuthor()->getEmail(),
            ];
        }, $posts);

        return $this->json($data);
    }

    #[Route('/posts/{id}/edit', name: 'api_post_edit', methods: ['POST'])]
    public function editPost(Post $post, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->logger->info('Attempting to edit post', [
            'post_id' => $post->getId(),
            'user' => $this->getUser()?->getEmail(),
            'user_roles' => $this->getUser()?->getRoles()
        ]);

        try {
            $this->denyAccessUnlessGranted('POST_EDIT', $post);
            
            $data = json_decode($request->getContent(), true);
            
            if (isset($data['title'])) {
                $post->setTitle($data['title']);
            }
            
            if (isset($data['content'])) {
                $post->setContent($data['content']);
            }

            $entityManager->flush();

            $this->logger->info('Post updated successfully', [
                'post_id' => $post->getId()
            ]);

            return $this->json([
                'message' => 'Post updated successfully',
                'id' => $post->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error updating post', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'error' => $e->getMessage()
            ], 403);
        }
    }
}
