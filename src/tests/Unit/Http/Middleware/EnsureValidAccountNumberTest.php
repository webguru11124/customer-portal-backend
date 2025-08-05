<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\EnsureValidAccountNumber;
use App\Models\User;
use Auth0\Laravel\Traits\ActingAsAuth0User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Mockery;

use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

use function random_int;

final class EnsureValidAccountNumberTest extends TestCase
{
    use ActingAsAuth0User;
    use RefreshDatabase;

    private const EXTERNAL_ID = 'autho';
    private const EMAIL = 'user@example.com';
    private const ANOTHER_EXTERNAL_ID = 'authi';
    private const ANOTHER_EMAIL = 'hacker@example.com';
    private const ACCOUNT_NUMBER = 123455678;
    private const ACCOUNT_NUMBER_PARAM = 'accountNumber';

    protected User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'external_id' => self::EXTERNAL_ID,
        ]);
        $this->user->accounts()->create([
            'office_id' => random_int(3, 11),
            'account_number' => self::ACCOUNT_NUMBER,
        ]);
    }

    public function test_it_throws_exception_if_account_number_parameter_is_missing(): void
    {
        $routeMock = Mockery::mock(Route::class);
        $routeMock
            ->expects('hasParameter')
            ->once()
            ->with(self::ACCOUNT_NUMBER_PARAM)
            ->andReturn(false);

        $requestMock = Mockery::mock(Request::class);
        $requestMock->expects('route')->withNoArgs()->once()->andReturn($routeMock);
        $requestMock->expects('route')->withArgs([self::ACCOUNT_NUMBER_PARAM])->never();
        $requestMock->expects('user')->never();

        $middleware = new EnsureValidAccountNumber();

        $this->expectException(HttpException::class);

        try {
            $middleware->handle($requestMock, static fn () => '');
        } catch (HttpException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());

            throw $exception;
        }
    }

    public function test_it_throws_exception_if_user_is_not_logged_in(): void
    {
        $routeMock = Mockery::mock(Route::class);
        $routeMock
            ->expects('hasParameter')
            ->withArgs([self::ACCOUNT_NUMBER_PARAM])
            ->once()
            ->andReturn(true);

        $requestMock = Mockery::mock(Request::class);
        $requestMock->expects('route')->withNoArgs()->once()->andReturn($routeMock);
        $requestMock->expects('route')->withArgs([self::ACCOUNT_NUMBER_PARAM])->once()->andReturn(self::ACCOUNT_NUMBER);
        $requestMock->expects('user')->once()->andReturnNull();

        $middleware = new EnsureValidAccountNumber();

        $this->expectException(HttpException::class);

        try {
            $middleware->handle($requestMock, static fn () => '');
        } catch (HttpException $exception) {
            $this->assertSame(Response::HTTP_UNAUTHORIZED, $exception->getStatusCode());

            throw $exception;
        }
    }

    public function test_it_throws_exception_if_account_number_is_invalid(): void
    {
        $userMock = Mockery::mock(User::class);
        $userMock->expects('hasAccountNumber')->with(self::ACCOUNT_NUMBER)->once()->andReturn(false);

        $routeMock = Mockery::mock(Route::class);
        $routeMock->expects('hasParameter')->with(self::ACCOUNT_NUMBER_PARAM)->once()->andReturn(true);

        $requestMock = Mockery::mock(Request::class);
        $requestMock->expects('route')->withNoArgs()->once()->andReturn($routeMock);
        $requestMock->expects('route')->withArgs([self::ACCOUNT_NUMBER_PARAM])->once()->andReturn(self::ACCOUNT_NUMBER);
        $requestMock->expects('user')->once()->andReturn($userMock);

        $middleware = new EnsureValidAccountNumber();

        $this->expectException(HttpException::class);

        try {
            $middleware->handle($requestMock, static fn () => '');
        } catch (HttpException $exception) {
            $this->assertSame(Response::HTTP_NOT_FOUND, $exception->getStatusCode());

            throw $exception;
        }
    }

    public function test_it_passes_if_account_number_is_valid(): void
    {
        $userMock = Mockery::mock(User::class);
        $userMock->expects('hasAccountNumber')->with(self::ACCOUNT_NUMBER)->once()->andReturn(true);

        $routeMock = Mockery::mock(Route::class);
        $routeMock->expects('hasParameter')->with(self::ACCOUNT_NUMBER_PARAM)->once()->andReturn(true);

        $requestMock = Mockery::mock(Request::class);
        $requestMock->expects('route')->withNoArgs()->once()->andReturn($routeMock);
        $requestMock->expects('route')->withArgs([self::ACCOUNT_NUMBER_PARAM])->once()->andReturn(self::ACCOUNT_NUMBER);
        $requestMock->expects('user')->once()->andReturn($userMock);

        $middleware = new EnsureValidAccountNumber();
        $middleware->handle($requestMock, fn () => $this->addToAssertionCount(1));
    }
}
