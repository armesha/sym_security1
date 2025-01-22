<?php

namespace App\Security\Voter;

use App\Entity\Post;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PostVoter extends Voter
{
    public const EDIT = 'POST_EDIT';

    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private LoggerInterface $logger
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        $this->logger->info('PostVoter::supports', [
            'attribute' => $attribute,
            'subject' => $subject instanceof Post ? 'Post#'.$subject->getId() : get_debug_type($subject)
        ]);
        
        return $attribute === self::EDIT && $subject instanceof Post;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            $this->logger->warning('PostVoter: User not found or invalid');
            return false;
        }

        /** @var Post $post */
        $post = $subject;

        $this->logger->info('PostVoter::voteOnAttribute', [
            'user' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'isAdmin' => $this->authorizationChecker->isGranted('ROLE_ADMIN'),
            'isApiAdmin' => $this->authorizationChecker->isGranted('ROLE_API_ADMIN'),
            'isPostAuthor' => $post->getAuthor() === $user
        ]);

        // ROLE_ADMIN, ROLE_SUPER_ADMIN, or ROLE_API_ADMIN can edit any post
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN') || 
            $this->authorizationChecker->isGranted('ROLE_API_ADMIN')) {
            return true;
        }

        // ROLE_USER or ROLE_API_USER can only edit their own posts
        if ($this->authorizationChecker->isGranted('ROLE_USER') || 
            $this->authorizationChecker->isGranted('ROLE_API_USER')) {
            return $post->getAuthor() === $user;
        }

        return false;
    }
}
