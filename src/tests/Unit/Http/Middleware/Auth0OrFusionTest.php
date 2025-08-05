<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\Auth0OrFusion;
use App\Models\User;
use Auth0\Laravel\Model\Stateless\User as Auth0User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class Auth0OrFusionTest extends TestCase
{
    private const FUSION_TOKEN = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6IjdjNjhjY2VlNyJ9.eyJhdWQiOiJiODRjY2FmOS1jMWM3LTRiYTgtODJjOS1lMjYzYmY5YjE1MmEiLCJleHAiOjE3MTM0MjgwMjMsImlhdCI6MTcxMzQyNDQyMywiaXNzIjoiYWNtZS5jb20iLCJzdWIiOiI2ZWNmYWZlMS0xNGQ2LTQ2MDgtYTJhOC05MzE4YmYxN2E0NzIiLCJqdGkiOiJlYzEyNzNmNC1mNDI4LTQzNjktOTdhYy0yZGU3MTViMTY4MTYiLCJhdXRoZW50aWNhdGlvblR5cGUiOiJQQVNTV09SRCIsImVtYWlsIjoidGVzdG92eWFra3BsZWFzZWlnbm9yZUBnbWFpbC5jb20iLCJlbWFpbF92ZXJpZmllZCI6dHJ1ZSwicHJlZmVycmVkX3VzZXJuYW1lIjoiVGVzdFBsZWFzZUlnbm9yZSIsImFwcGxpY2F0aW9uSWQiOiJiODRjY2FmOS1jMWM3LTRiYTgtODJjOS1lMjYzYmY5YjE1MmEiLCJyb2xlcyI6W10sImF1dGhfdGltZSI6MTcxMzQyNDQyMywidGlkIjoiZmQxYTQwMTItMjllYS00ZmJmLWFhNjYtM2Q4OGZiY2VhN2VjIn0.VBbyo8DpURzKlhr9cL8iC5ao6-NAJvUCJx_4bkRtqbA';
    private const EMAIL = 'test@example.com';
    private const EXTERNAL_ID = 'auth0|638a07d78779a00e526a4ce4';
    private const FUSION_ID = '6ecfafe1-14d6-4608-a2a8-9318bf17a472';

    protected Guard|Mockery\MockInterface $auth0GuardMock;
    protected Guard|Mockery\MockInterface $fusionGuardMock;

    public function test_it_continues_when_auth0_user_found(): void
    {
        $user = new Auth0User([
            'sub' => self::EXTERNAL_ID,
            'email' => self::EMAIL,
            'email_verified' => true,
        ]);

        $this->setUpAuthFactoryMock();
        $this->auth0GuardMock
            ->expects('user')
            ->once()
            ->andReturn($user);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->once()
            ->andReturn('auth0');
        $middleware = new Auth0OrFusion();

        $middleware->handle($request, fn (Request $r) => $this->assertSame($request, $r));
    }

    public function test_it_continues_when_fusion_user_found(): void
    {
        $user = User::factory()->make([
            User::FUSIONCOLUMN => self::FUSION_ID,
            'email' => self::EMAIL,
            'email_verified' => true,
        ]);

        $authFactoryMock = $this->setUpAuthFactoryMock();
        $authFactoryMock
            ->expects('guard')
            ->with('fusion')
            ->once()
            ->andReturn($this->fusionGuardMock);
        $this->auth0GuardMock
            ->expects('user')
            ->once()
            ->andReturn(null);
        $this->fusionGuardMock
            ->expects('user')
            ->once()
            ->andReturn($user);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->once()
            ->andReturn('fusion');
        $request->shouldReceive('bearerToken')
            ->once()
            ->andReturn(self::FUSION_TOKEN);
        $middleware = new Auth0OrFusion();

        $middleware->handle($request, fn (Request $r) => $this->assertSame($request, $r));
    }

    public function test_it_throws_exception_unauthorized_when_no_token_found(): void
    {
        $authFactoryMock = $this->setUpAuthFactoryMock();
        $authFactoryMock
            ->expects('guard')
            ->with('fusion')
            ->never();
        $this->auth0GuardMock
            ->expects('user')
            ->once()
            ->andReturn(null);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->once()
            ->andReturn('fusion');
        $request->shouldReceive('bearerToken')
            ->once()
            ->andReturn(null);
        $middleware = new Auth0OrFusion();

        $this->expectException(HttpException::class);

        try {
            $middleware->handle($request, static fn (Request $r) => '');
        } catch (HttpException $exception) {
            $this->assertSame(Response::HTTP_UNAUTHORIZED, $exception->getStatusCode());

            throw $exception;
        }
    }

    public function test_it_throws_exception_unauthorized_when_no_user_found(): void
    {
        $authFactoryMock = $this->setUpAuthFactoryMock();
        $authFactoryMock
            ->expects('guard')
            ->with('fusion')
            ->once()
            ->andReturn($this->fusionGuardMock);
        $this->auth0GuardMock
            ->expects('user')
            ->once()
            ->andReturn(null);
        $this->fusionGuardMock
            ->expects('user')
            ->once()
            ->andReturn(null);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('header')
            ->with('X-Auth-Type')
            ->once()
            ->andReturn('fusion');
        $request->shouldReceive('bearerToken')
            ->once()
            ->andReturn(self::FUSION_TOKEN);
        $middleware = new Auth0OrFusion();

        $this->expectException(HttpException::class);

        try {
            $middleware->handle($request, static fn (Request $r) => '');
        } catch (HttpException $exception) {
            $this->assertSame(Response::HTTP_UNAUTHORIZED, $exception->getStatusCode());

            throw $exception;
        }
    }

    protected function setUpAuthFactoryMock(): Mockery\MockInterface
    {
        $this->auth0GuardMock = Mockery::mock(Guard::class);
        $this->fusionGuardMock = Mockery::mock(Guard::class);

        $authFactoryMock = Mockery::mock(AuthFactory::class);
        $authFactoryMock
            ->expects('guard')
            ->with('auth0')
            ->once()
            ->andReturn($this->auth0GuardMock);
        $this->instance(AuthFactory::class, $authFactoryMock);

        return $authFactoryMock;
    }
}
