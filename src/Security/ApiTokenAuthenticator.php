<?php

namespace App\Security;

use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private UserRepository $userRepository,
        private LoggerInterface $logger
    ) {}

    public function supports(Request $request): ?bool
    {
        $supported = str_starts_with($request->getPathInfo(), '/api/');
        $this->logger->info('ApiTokenAuthenticator::supports', [
            'path' => $request->getPathInfo(),
            'supported' => $supported
        ]);
        return $supported;
    }

    public function authenticate(Request $request): Passport
    {
        $apiToken = $request->headers->get('X-API-TOKEN');
        if (null === $apiToken) {
            $this->logger->warning('No API token provided');
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        $this->logger->info('ApiTokenAuthenticator::authenticate', [
            'token' => $apiToken
        ]);

        return new SelfValidatingPassport(
            new UserBadge($apiToken, function($apiToken) {
                $user = $this->userRepository->findOneBy(['email' => $apiToken]);
                if (!$user) {
                    $this->logger->warning('Invalid API Token', ['token' => $apiToken]);
                    throw new CustomUserMessageAuthenticationException('Invalid API Token');
                }
                $this->logger->info('User authenticated', [
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles()
                ]);
                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $this->logger->info('Authentication success', [
            'user' => $token->getUserIdentifier(),
            'roles' => $token->getRoleNames()
        ]);
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->logger->error('Authentication failure', [
            'error' => $exception->getMessage()
        ]);

        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}
