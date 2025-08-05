<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\DTO\MagicLink\ValidationErrorDTO;
use App\Http\Middleware\MagicToken;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class MagicTokenTest extends TestCase
{
    private const EMAIL = 'test@example.com';

    protected Guard|Mockery\MockInterface $magicLinkGuardMock;

    public function test_it_continues_when_magic_link_user_found(): void
    {
        $user = User::factory()->make([
            'email' => self::EMAIL,
            'email_verified' => true,
        ]);
        $this->magicLinkGuardMock = Mockery::mock(Guard::class);
        $this->magicLinkGuardMock->expects('user')
            ->once()
            ->andReturn($user);
        $authFactoryMock = Mockery::mock(AuthFactory::class);
        $authFactoryMock->expects('guard')
            ->with('magiclinkguard')
            ->once()
            ->andReturn($this->magicLinkGuardMock);
        $this->instance(AuthFactory::class, $authFactoryMock);
        Auth::expects('shouldUse')
            ->with('magiclinkguard')
            ->once();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->once()
            ->andReturn('MagicLink');

        $middleware = new MagicToken();

        $middleware->handle($request, fn (Request $r) => $this->assertSame($request, $r));
    }

    public function test_it_throws_exception_on_validation_error(): void
    {
        $error = new ValidationErrorDTO(
            ValidationErrorDTO::EXPIRED_TOKEN_CODE,
            ValidationErrorDTO::INVALID_TOKEN_MESSAGE
        );
        $this->magicLinkGuardMock = Mockery::mock(Guard::class);
        $this->magicLinkGuardMock->expects('user')
            ->once()
            ->andReturn(null);
        $this->magicLinkGuardMock->expects('getValidationError')
            ->once()
            ->andReturn($error);

        $authFactoryMock = Mockery::mock(AuthFactory::class);
        $authFactoryMock->expects('guard')
            ->with('magiclinkguard')
            ->once()
            ->andReturn($this->magicLinkGuardMock);
        $this->instance(AuthFactory::class, $authFactoryMock);
        Auth::expects('shouldUse')
            ->with('magiclinkguard')
            ->never();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->once()
            ->andReturn('MagicLink');

        $this->expectException(HttpException::class);
        $middleware = new MagicToken();

        try {
            $middleware->handle($request, static fn (Request $r) => '');
        } catch (HttpException $exception) {
            $this->assertSame(ValidationErrorDTO::EXPIRED_TOKEN_CODE, $exception->getStatusCode());

            throw $exception;
        }
    }

    public function test_it_throws_exception_when_no_magic_link_user_found(): void
    {
        $this->magicLinkGuardMock = Mockery::mock(Guard::class);
        $this->magicLinkGuardMock->expects('user')
            ->never();
        $authFactoryMock = Mockery::mock(AuthFactory::class);
        $authFactoryMock->expects('guard')
            ->with('magiclinkguard')
            ->never();
        $this->instance(AuthFactory::class, $authFactoryMock);
        Auth::expects('shouldUse')
            ->with('magiclinkguard')
            ->never();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->once()
            ->andReturn('Auth0');

        $this->expectException(HttpException::class);

        $middleware = new MagicToken();
        try {
            $middleware->handle($request, static fn (Request $r) => '');
        } catch (HttpException $exception) {
            $this->assertSame(Response::HTTP_UNAUTHORIZED, $exception->getStatusCode());

            throw $exception;
        }
    }
}
