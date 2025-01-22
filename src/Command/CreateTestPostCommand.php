<?php

namespace App\Command;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-test-post',
    description: 'Creates a test post'
)]
class CreateTestPostCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'user@example.com']);

        if (!$user) {
            $output->writeln('User not found!');
            return Command::FAILURE;
        }

        $post = new Post();
        $post->setTitle('Test Post');
        $post->setContent('This is a test post content.');
        $post->setAuthor($user);

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        $output->writeln('Test post created successfully!');

        return Command::SUCCESS;
    }
}
