<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Password-less login for development: signs in as any existing user.
 *
 * Only registered on the firewall in the dev and test environments, see config/packages/security.yaml.
 */
class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public function __construct(
        private readonly RouterInterface $router,
    ) {
    }

    #[\Override]
    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate('app_login');
    }

    #[\Override]
    public function authenticate(Request $request): Passport
    {
        $identifier = (string) $request->request->get('identifier');

        if ('' === $identifier) {
            throw new AuthenticationException('User identifier cannot be empty.');
        }

        return new SelfValidatingPassport(
            new UserBadge($identifier),
            [
                new CsrfTokenBadge(
                    'login',
                    (string) $request->request->get('_csrf_token')
                ),
                new RememberMeBadge(),
            ]
        );
    }

    #[\Override]
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): RedirectResponse {
        if ($targetPath = $this->getTargetPath(
            $request->getSession(),
            $firewallName
        )
        ) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->router->generate('app_default'));
    }
}
