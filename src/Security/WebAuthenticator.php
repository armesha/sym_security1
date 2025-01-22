<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class WebAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';
    public const ADMIN_LOGIN_ROUTE = 'admin_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UserRepository $userRepository
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('_username', '');
        $password = $request->request->get('_password', '');
        $csrfToken = $request->request->get('_csrf_token');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email, function($userIdentifier) {
                $user = $this->userRepository->findOneBy(['email' => $userIdentifier]);
                
                if (!$user) {
                    throw new CustomUserMessageAuthenticationException('Email could not be found.');
                }

                if (in_array('ROLE_API_USER', $user->getRoles())) {
                    throw new CustomUserMessageAuthenticationException('API users cannot use web login. Please use API endpoints.');
                }

                return $user;
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        if ($firewallName === 'admin') {
            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        }

        return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
    }

    protected function getLoginUrl(Request $request): string
    {
        if (str_starts_with($request->getPathInfo(), '/admin')) {
            return $this->urlGenerator->generate(self::ADMIN_LOGIN_ROUTE);
        }

        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
