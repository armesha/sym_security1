<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new user'
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'The email of the user')
            ->addArgument('password', InputArgument::REQUIRED, 'The password of the user')
            ->addArgument('roles', InputArgument::REQUIRED, 'The roles of the user (comma-separated)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = new User();
        $user->setEmail($input->getArgument('email'));
        
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $input->getArgument('password')
        );
        $user->setPassword($hashedPassword);
        
        $roles = explode(',', $input->getArgument('roles'));
        $user->setRoles($roles);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('User successfully created!');

        return Command::SUCCESS;
    }
}
