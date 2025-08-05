<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\Authorize;
use App\Models\User;
use App\Services\UserService;
use Auth0\Laravel\Model\Stateless\User as Auth0User;
use Auth0\Laravel\Traits\ActingAsAuth0User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Payload;

final class AuthorizeTest extends TestCase
{
    use ActingAsAuth0User;
    use RandomIntTestData;

    private const EMAIL = 'test@example.com';
    private const EXTERNAL_ID = 'auth0|638a07d78779a00e526a4ce4';
    private const FUSION_ID = '6ecfafe1-14d6-4608-a2a8-9318bf17a472';

    protected Guard|MockInterface $auth0GuardMock;
    protected Guard|MockInterface $fusionGuardMock;
    protected Guard|MockInterface $magicGuardMock;
    protected UserService|MockInterface $userServiceMock;

    /**
     * Prevents potential memory leaks during tests.
     */
    public function tearDown(): void
    {
        parent::tearDown();

        unset($this->auth0GuardMock);
        unset($this->fusionGuardMock);
        unset($this->magicGuardMock);
        unset($this->userServiceMock);
    }

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function test_it_throws_exception_when_auth0_user_not_authorized(?Authenticatable $user): void
    {
        $this->setUpAuth0AuthFactoryMock();

        $this->auth0GuardMock->expects('user')->once()->andReturn($user);
        $this->userServiceMock->expects('findUserByEmailAndExtId')->never();

        $request = $this->getRequestMock('Auth0');
        $middleware = new Authorize($this->userServiceMock);

        $this->expectException(HttpException::class);

        try {
            $middleware->handle($request, static fn (Request $r) => '');
        } catch (HttpException $exception) {
            $this->assertSame(Response::HTTP_UNAUTHORIZED, $exception->getStatusCode());

            throw $exception;
        }
    }

    public function unauthorizedUserDataProvider(): array
    {
        return [
            'No email in JWT token' => [new Auth0User()],
            'No `email_verified` flag in token' => [new Auth0User(['email' => self::EMAIL])],
            'Email is not verified' => [new Auth0User(['email' => self::EMAIL, 'email_verified' => false])],
        ];
    }

    public function test_it_throws_exception_when_auth0_user_cannot_be_found_or_created(): void
    {
        $this->setUpAuth0AuthFactoryMock();
        $this->setUpAuth0User();

        $this->userServiceMock
            ->expects('findUserByEmailAndExtId')
            ->once()
            ->with(self::EMAIL, self::EXTERNAL_ID, User::AUTH0COLUMN)
            ->andReturnNull();
        $this->userServiceMock
            ->expects('createOrUpdateUserWithExternalId')
            ->once()
            ->with(self::EXTERNAL_ID, self::EMAIL, User::AUTH0COLUMN)
            ->andReturnNull();

        Auth::expects('login')->never();
        Auth::expects('shouldUse')->never();

        $request = $this->getRequestMock('Auth0');
        $middleware = new Authorize($this->userServiceMock);

        $this->expectException(HttpException::class);

        try {
            $middleware->handle($request, static fn (Request $r) => '');
        } catch (HttpException $exception) {
            $this->assertSame(Response::HTTP_PRECONDITION_FAILED, $exception->getStatusCode());

            throw $exception;
        }
    }

    public function test_it_logs_in_found_auth0_user(): void
    {
        $this->setUpAuth0AuthFactoryMock();
        $this->setUpAuth0User();

        $user = Mockery::mock(User::class);

        $this->userServiceMock
            ->expects('findUserByEmailAndExtId')
            ->once()
            ->with(self::EMAIL, self::EXTERNAL_ID, User::AUTH0COLUMN)
            ->andReturn($user);
        $this->userServiceMock
            ->expects('createOrUpdateUserWithExternalId')
            ->never();
        $this->userServiceMock
            ->expects('syncUserAccounts')
            ->with($user)
            ->once();

        Auth::expects('login')->once()->with($user);
        Auth::expects('shouldUse')->once()->with('auth0');

        $request = $this->getRequestMock('Auth0');
        $middleware = new Authorize($this->userServiceMock);

        $middleware->handle($request, fn (Request $r) => $this->assertSame($request, $r));
    }

    public function test_it_creates_and_logs_in_new_auth0_user(): void
    {
        $this->setUpAuth0AuthFactoryMock();
        $this->setUpAuth0User();

        $user = Mockery::mock(User::class);

        $this->userServiceMock
            ->expects('findUserByEmailAndExtId')
            ->once()
            ->with(self::EMAIL, self::EXTERNAL_ID, User::AUTH0COLUMN)
            ->andReturnNull();
        $this->userServiceMock
            ->expects('createOrUpdateUserWithExternalId')
            ->once()
            ->with(self::EXTERNAL_ID, self::EMAIL, User::AUTH0COLUMN)
            ->andReturn($user);
        $this->userServiceMock
            ->expects('syncUserAccounts')
            ->with($user)
            ->once();

        Auth::expects('login')->once()->with($user);
        Auth::expects('shouldUse')->once()->with('auth0');

        $request = $this->getRequestMock('Auth0');
        $middleware = new Authorize($this->userServiceMock);

        $middleware->handle($request, fn (Request $r) => $this->assertSame($request, $r));
    }

    public function test_it_logs_in_found_fusion_user(): void
    {
        $this->setUpFusionAuthFactoryMock();
        $user = $this->getFusionUser();
        $this->setUpFusionGuard($user);

        $this->userServiceMock
            ->expects('findUserByEmailAndExtId')
            ->once()
            ->with(self::EMAIL, self::FUSION_ID, User::FUSIONCOLUMN)
            ->andReturn($user);
        $this->userServiceMock
            ->expects('createOrUpdateUserWithExternalId')
            ->never();
        $this->userServiceMock
            ->expects('syncUserAccounts')
            ->with($user)
            ->once();

        Auth::expects('shouldUse')->once()->with('fusion');

        $request = $this->getRequestMock('fusion');
        $middleware = new Authorize($this->userServiceMock);

        $middleware->handle($request, fn (Request $r) => $this->assertSame($request, $r));
    }

    public function test_it_creates_and_logs_in_new_fusion_user(): void
    {
        $this->setUpFusionAuthFactoryMock();
        $user = $this->getFusionUser();
        $this->setUpFusionGuard($user);

        $this->userServiceMock
            ->expects('findUserByEmailAndExtId')
            ->once()
            ->with(self::EMAIL, self::FUSION_ID, User::FUSIONCOLUMN)
            ->andReturnNull();
        $this->userServiceMock
            ->expects('createOrUpdateUserWithExternalId')
            ->once()
            ->with(self::FUSION_ID, self::EMAIL, User::FUSIONCOLUMN)
            ->andReturn($user);
        $this->userServiceMock
            ->expects('syncUserAccounts')
            ->with($user)
            ->once();

        Auth::expects('shouldUse')->once()->with('fusion');

        $request = $this->getRequestMock('fusion');
        $middleware = new Authorize($this->userServiceMock);

        $middleware->handle($request, fn (Request $r) => $this->assertSame($request, $r));
    }

    public function test_it_throws_exception_when_fusion_user_cannot_be_found_or_created(): void
    {
        $this->setUpFusionAuthFactoryMock();
        $user = $this->getFusionUser();

        $this->auth0GuardMock
            ->expects('user')
            ->once()
            ->andReturn(null);
        $this->fusionGuardMock
            ->expects('payload')
            ->once()
            ->andReturn(Mockery::mock(Payload::class));
        $this->fusionGuardMock
            ->expects('user')
            ->once()
            ->andReturn($user);
        $this->userServiceMock
            ->expects('findUserByEmailAndExtId')
            ->once()
            ->with(self::EMAIL, self::FUSION_ID, User::FUSIONCOLUMN)
            ->andReturnNull();
        $this->userServiceMock
            ->expects('createOrUpdateUserWithExternalId')
            ->once()
            ->with(self::FUSION_ID, self::EMAIL, User::FUSIONCOLUMN)
            ->andReturnNull();

        Auth::expects('shouldUse')->never();

        $request = $this->getRequestMock('fusion');
        $middleware = new Authorize($this->userServiceMock);

        $this->expectException(HttpException::class);

        try {
            $middleware->handle($request, static fn (Request $r) => '');
        } catch (HttpException $exception) {
            $this->assertSame(Response::HTTP_PRECONDITION_FAILED, $exception->getStatusCode());

            throw $exception;
        }
    }

    public function test_it_throws_exception_when_fusion_user_not_authorized(): void
    {
        $this->setUpFusionAuthFactoryMock();

        $this->auth0GuardMock
            ->expects('user')
            ->once()
            ->andReturn(null);
        $this->fusionGuardMock
            ->expects('payload')
            ->once()
            ->andReturn(Mockery::mock(Payload::class));
        $this->fusionGuardMock
            ->expects('user')
            ->once()
            ->andReturn(null);

        Auth::expects('shouldUse')->never();

        $request = $this->getRequestMock('fusion');
        $middleware = new Authorize($this->userServiceMock);

        $this->expectException(HttpException::class);

        try {
            $middleware->handle($request, static fn (Request $r) => '');
        } catch (HttpException $exception) {
            $this->assertSame(Response::HTTP_UNAUTHORIZED, $exception->getStatusCode());

            throw $exception;
        }
    }

    /**
     * @dataProvider invalidFusionPayloadDataProvider
     */
    public function test_it_throws_exception_on_invalid_fusion_payload(\Throwable $exception): void
    {
        $this->userServiceMock = Mockery::mock(UserService::class);
        $this->auth0GuardMock = Mockery::mock(Guard::class);
        $this->fusionGuardMock = Mockery::mock(Guard::class);

        $authFactoryMock = Mockery::mock(AuthFactory::class);
        $authFactoryMock
            ->expects('guard')
            ->with('auth0')
            ->once()
            ->andReturn($this->auth0GuardMock);

        $authFactoryMock
            ->expects('guard')
            ->with('fusion')
            ->once()
            ->andReturn($this->fusionGuardMock);

        $this->instance(AuthFactory::class, $authFactoryMock);

        $this->auth0GuardMock
            ->expects('user')
            ->once()
            ->andReturn(null);
        $this->fusionGuardMock
            ->expects('payload')
            ->once()
            ->andThrow($exception);

        Auth::expects('shouldUse')->never();

        $request = $this->getRequestMock('Auth0');
        $middleware = new Authorize($this->userServiceMock);

        $this->expectException(HttpException::class);

        try {
            $middleware->handle($request, static fn (Request $r) => '');
        } catch (HttpException $exception) {
            $this->assertSame(Response::HTTP_UNAUTHORIZED, $exception->getStatusCode());

            throw $exception;
        }
    }

    public function invalidFusionPayloadDataProvider(): array
    {
        return [
            'invalid audience' => [new TokenInvalidException('Audience (aud) invalid')],
            'invalid issuer' => [new TokenInvalidException('Issuer (iss) invalid')],
            'email is missing' => [new TokenInvalidException('Email is missing from the token')],
            'email not verified' => [new TokenInvalidException('Email is not verified')],
        ];
    }

    public function test_it_returns_error_when_email_not_set_in_token(): void
    {
        $this->actingAsAuth0User(['sub' => self::EXTERNAL_ID]);

        $this->getJson(route('api.user.accounts'))
            ->assertUnauthorized();
    }

    public function test_it_logs_in_found_magic_user(): void
    {
        $this->setUpMagicAuthFactoryMock();
        $user = $this->getMagicUser($this->getTestAccountNumber());
        $this->setUpMagicGuard($user);

        $this->userServiceMock
            ->expects('createOrUpdateUserWithExternalId')
            ->with(self::EMAIL, self::EMAIL, 'email')
            ->never();
        $this->userServiceMock
            ->expects('syncUserAccounts')
            ->with($user)
            ->once();

        Auth::expects('shouldUse')->once()->with('magicjwtguard');

        $request = $this->getRequestMock('MagicLink');
        $middleware = new Authorize($this->userServiceMock);

        $middleware->handle($request, fn (Request $r) => $this->assertSame($request, $r));
    }

    public function test_it_creates_and_logs_in_new_magic_user(): void
    {
        $this->setUpMagicAuthFactoryMock();
        $user = $this->getMagicUser(null);
        $this->setUpMagicGuard($user);

        $this->userServiceMock
            ->expects('createOrUpdateUserWithExternalId')
            ->once()
            ->with(self::EMAIL, self::EMAIL, 'email')
            ->andReturn($user);
        $this->userServiceMock
            ->expects('syncUserAccounts')
            ->with($user)
            ->once();

        Auth::expects('shouldUse')->once()->with('magicjwtguard');

        $request = $this->getRequestMock('MagicLink');
        $middleware = new Authorize($this->userServiceMock);

        $middleware->handle($request, fn (Request $r) => $this->assertSame($request, $r));
    }

    public function test_it_throws_exception_when_magic_user_cannot_be_found_or_created(): void
    {
        $this->setUpMagicAuthFactoryMock();
        $user = $this->getMagicUser(null);
        $this->setUpMagicGuard($user);

        $this->userServiceMock
            ->expects('createOrUpdateUserWithExternalId')
            ->once()
            ->with(self::EMAIL, self::EMAIL, 'email')
            ->andReturnNull();

        Auth::expects('shouldUse')->never();

        $request = $this->getRequestMock('MagicLink');
        $middleware = new Authorize($this->userServiceMock);

        $this->expectException(HttpException::class);

        try {
            $middleware->handle($request, static fn (Request $r) => '');
        } catch (HttpException $exception) {
            $this->assertSame(Response::HTTP_PRECONDITION_FAILED, $exception->getStatusCode());

            throw $exception;
        }
    }

    public function test_it_throws_exception_when_magic_user_not_authorized(): void
    {
        $this->setUpMagicAuthFactoryMock();
        $this->magicGuardMock
            ->expects('payload')
            ->once()
            ->andReturn(Mockery::mock(Payload::class));
        $this->magicGuardMock
            ->expects('user')
            ->once()
            ->andReturn(null);

        Auth::expects('shouldUse')->never();

        $request = $this->getRequestMock('MagicLink');
        $middleware = new Authorize($this->userServiceMock);

        $this->expectException(HttpException::class);

        try {
            $middleware->handle($request, static fn (Request $r) => '');
        } catch (HttpException $exception) {
            $this->assertSame(Response::HTTP_UNAUTHORIZED, $exception->getStatusCode());

            throw $exception;
        }
    }

    public function test_it_throws_exception_on_invalid_magic_payload(): void
    {
        $this->setUpMagicAuthFactoryMock();
        $exception = new JWTException('Token could not be parsed from the request.');
        $this->magicGuardMock
            ->expects('payload')
            ->once()
            ->andThrow($exception);
        $this->userServiceMock = Mockery::mock(UserService::class);

        Auth::expects('shouldUse')->never();

        $request = $this->getRequestMock('MagicLink');
        $middleware = new Authorize($this->userServiceMock);

        $this->expectException(HttpException::class);

        try {
            $middleware->handle($request, static fn (Request $r) => '');
        } catch (HttpException $exception) {
            $this->assertSame(Response::HTTP_UNAUTHORIZED, $exception->getStatusCode());

            throw $exception;
        }
    }

    private function setUpAuth0AuthFactoryMock(): void
    {
        $this->userServiceMock = Mockery::mock(UserService::class);
        $this->auth0GuardMock = Mockery::mock(Guard::class);

        $authFactoryMock = Mockery::mock(AuthFactory::class);
        $authFactoryMock
            ->expects('guard')
            ->with('auth0')
            ->once()
            ->andReturn($this->auth0GuardMock);

        $this->instance(AuthFactory::class, $authFactoryMock);
    }

    private function setUpFusionAuthFactoryMock(): void
    {
        $this->userServiceMock = Mockery::mock(UserService::class);
        $this->auth0GuardMock = Mockery::mock(Guard::class);
        $this->fusionGuardMock = Mockery::mock(Guard::class);

        $authFactoryMock = Mockery::mock(AuthFactory::class);
        $authFactoryMock
            ->expects('guard')
            ->with('auth0')
            ->once()
            ->andReturn($this->auth0GuardMock);

        $authFactoryMock
            ->expects('guard')
            ->with('fusion')
            ->once()
            ->andReturn($this->fusionGuardMock);

        $this->instance(AuthFactory::class, $authFactoryMock);
    }

    private function setUpMagicAuthFactoryMock(): void
    {
        $this->userServiceMock = Mockery::mock(UserService::class);
        $this->auth0GuardMock = Mockery::mock(Guard::class);
        $this->fusionGuardMock = Mockery::mock(Guard::class);
        $this->magicGuardMock = Mockery::mock(Guard::class);

        $authFactoryMock = Mockery::mock(AuthFactory::class);
        $authFactoryMock
            ->expects('guard')
            ->with('magicjwtguard')
            ->once()
            ->andReturn($this->magicGuardMock);

        $this->instance(AuthFactory::class, $authFactoryMock);
    }

    private function setUpAuth0User(): void
    {
        $user = new Auth0User([
            'sub' => self::EXTERNAL_ID,
            'email' => self::EMAIL,
            'email_verified' => true,
        ]);

        $this->auth0GuardMock
            ->expects('user')
            ->once()
            ->andReturn($user);
    }

    private function setUpFusionGuard(User $user): void
    {
        $this->auth0GuardMock
            ->expects('user')
            ->once()
            ->andReturn(null);

        $this->fusionGuardMock
            ->expects('user')
            ->once()
            ->andReturn($user);
        ;
        $this->fusionGuardMock
            ->expects('payload')
            ->once()
            ->andReturn(Mockery::mock(Payload::class));
    }

    private function getFusionUser(): User
    {
        return User::factory()->make([
            User::FUSIONCOLUMN => self::FUSION_ID,
            'email' => self::EMAIL,
            'email_verified' => true,
        ]);
    }

    private function getMagicUser(int|null $id): User
    {
        return User::factory()->make([
            'id' => $id,
            'email' => self::EMAIL,
        ]);
    }

    private function setUpMagicGuard(User $user): void
    {
        $this->magicGuardMock
            ->expects('user')
            ->once()
            ->andReturn($user);
        ;
        $this->magicGuardMock
            ->expects('payload')
            ->once()
            ->andReturn(Mockery::mock(Payload::class));
    }

    /**
     * @param string $auth
     * @return Request|MockInterface
     */
    protected function getRequestMock(string $auth): MockInterface
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->once()
            ->andReturn($auth);

        return $request;
    }
}
