<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\LoginFormAuthenticator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

#[AllowMockObjectsWithoutExpectations]
class LoginFormAuthenticatorTest extends TestCase
{
    private LoginFormAuthenticator $authenticator;

    private RouterInterface&MockObject $router;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->authenticator = new LoginFormAuthenticator($this->router);
    }

    public function testAuthenticateReturnsPassport(): void
    {
        $request = new Request([], ['identifier' => 'testuser', '_csrf_token' => 'token123']);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $passport = $this->authenticator->authenticate($request);

        self::assertInstanceOf(SelfValidatingPassport::class, $passport);
    }

    public function testAuthenticateThrowsOnEmptyIdentifier(): void
    {
        $request = new Request([], ['identifier' => '', '_csrf_token' => 'token123']);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $this->expectException(AuthenticationException::class);
        $this->authenticator->authenticate($request);
    }

    public function testSupportsOnlyLoginPost(): void
    {
        $this->router->expects(self::atLeastOnce())->method('generate')->with('app_login')->willReturn('/login');

        self::assertTrue($this->authenticator->supports(Request::create('/login', 'POST')));
        self::assertFalse($this->authenticator->supports(Request::create('/login', 'GET')));
    }

    public function testOnAuthenticationSuccessRedirectsToTargetPath(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('_security.main.target_path', '/dashboard');

        $request = new Request();
        $request->setSession($session);

        $response = $this->authenticator->onAuthenticationSuccess(
            $request,
            $this->createStub(TokenInterface::class),
            'main'
        );

        self::assertSame('/dashboard', $response->getTargetUrl());
    }

    public function testOnAuthenticationSuccessRedirectsToDefault(): void
    {
        $this->router->expects(self::once())->method('generate')->with('app_default')->willReturn('/');

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        $response = $this->authenticator->onAuthenticationSuccess(
            $request,
            $this->createStub(TokenInterface::class),
            'main'
        );

        self::assertSame('/', $response->getTargetUrl());
    }
}
