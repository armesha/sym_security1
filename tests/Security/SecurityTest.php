<?php

namespace App\Tests\Security;

use App\Entity\Post;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class SecurityTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testApiUserCannotAccessAdmin(): void
    {
        $user = new User();
        $user->setEmail('api@test.com');
        $user->setPassword('password');
        $user->setRoles(['ROLE_API_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        $this->client->request('GET', '/admin/dashboard');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testSuperAdminCanEditAnyPost(): void
    {
        $admin = new User();
        $admin->setEmail('super@test.com');
        $admin->setPassword('password');
        $admin->setRoles(['ROLE_SUPER_ADMIN']);

        $regularUser = new User();
        $regularUser->setEmail('user@test.com');
        $regularUser->setPassword('password');
        $regularUser->setRoles(['ROLE_USER']);

        $post = new Post();
        $post->setTitle('Test Post');
        $post->setContent('Test Content');
        $post->setAuthor($regularUser);

        $this->entityManager->persist($admin);
        $this->entityManager->persist($regularUser);
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        $token = new UsernamePasswordToken($admin, 'main', $admin->getRoles());
        $this->client->getContainer()->get('security.token_storage')->setToken($token);

        $this->assertTrue(
            $this->client->getContainer()
                ->get('security.authorization_checker')
                ->isGranted('POST_EDIT', $post)
        );
    }

    public function testRegularUserCannotEditOtherUsersPosts(): void
    {
        $user1 = new User();
        $user1->setEmail('user1@test.com');
        $user1->setPassword('password');
        $user1->setRoles(['ROLE_USER']);

        $user2 = new User();
        $user2->setEmail('user2@test.com');
        $user2->setPassword('password');
        $user2->setRoles(['ROLE_USER']);

        $post = new Post();
        $post->setTitle('Test Post');
        $post->setContent('Test Content');
        $post->setAuthor($user2);

        $this->entityManager->persist($user1);
        $this->entityManager->persist($user2);
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        $token = new UsernamePasswordToken($user1, 'main', $user1->getRoles());
        $this->client->getContainer()->get('security.token_storage')->setToken($token);

        $this->assertFalse(
            $this->client->getContainer()
                ->get('security.authorization_checker')
                ->isGranted('POST_EDIT', $post)
        );
    }
}
