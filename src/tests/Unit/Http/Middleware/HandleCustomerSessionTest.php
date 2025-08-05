<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\HandleCustomerSession;
use App\Models\User;
use App\Services\CustomerSessionService;
use Auth0\Laravel\Traits\ActingAsAuth0User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class HandleCustomerSessionTest extends TestCase
{
    use ActingAsAuth0User;
    use RefreshDatabase;
    use RandomIntTestData;

    protected MockInterface|CustomerSessionService $customerSessionServiceMock;
    protected HandleCustomerSession $subject;

    private const ACCOUNT_NUMBER_PARAMETER_NAME = 'accountNumber';

    public function setUp(): void
    {
        parent::setUp();

        $this->customerSessionServiceMock = Mockery::mock(CustomerSessionService::class);
        $this->subject = new HandleCustomerSession($this->customerSessionServiceMock);
    }

    public function test_it_handles_customer_session(): void
    {
        $accountNumber = $this->getTestAccountNumber();

        $userMock = Mockery::mock(User::class);

        $routeMock = Mockery::mock(Route::class);
        $routeMock->shouldReceive('hasParameter')
            ->withArgs([self::ACCOUNT_NUMBER_PARAMETER_NAME])
            ->once()
            ->andReturn(true);

        $requestMock = Mockery::mock(Request::class);
        $requestMock->shouldReceive('route')
            ->withNoArgs()
            ->once()
            ->andReturn($routeMock);

        $requestMock->shouldReceive('route')
            ->withArgs([self::ACCOUNT_NUMBER_PARAMETER_NAME])
            ->once()
            ->andReturn($accountNumber);

        $requestMock
            ->shouldReceive('user')
            ->once()
            ->andReturn($userMock);

        $this->customerSessionServiceMock->shouldReceive('handleSession')
            ->withArgs([$accountNumber])
            ->once()
            ->andReturn(null);

        $this->subject->handle($requestMock, fn () => true);
    }

    public function test_it_does_not_handle_session_if_account_number_parameter_is_missing(): void
    {
        $routeMock = Mockery::mock(Route::class);
        $routeMock->shouldReceive('hasParameter')
            ->withArgs([self::ACCOUNT_NUMBER_PARAMETER_NAME])
            ->andReturn(false);

        $requestMock = Mockery::mock(Request::class);
        $requestMock->shouldReceive('route')
            ->withNoArgs()
            ->once()
            ->andReturn($routeMock);

        $this->customerSessionServiceMock->shouldReceive('handleSession')
            ->never();

        $this->subject->handle($requestMock, fn () => true);
    }

    public function test_it_does_not_handle_session_if_user_does_not_logged_in(): void
    {
        $routeMock = Mockery::mock(Route::class);
        $routeMock->shouldReceive('hasParameter')
            ->andReturn(true);

        $requestMock = Mockery::mock(Request::class);
        $requestMock->shouldReceive('route')
            ->withNoArgs()
            ->andReturn($routeMock);

        $requestMock->shouldReceive('user')
            ->andReturn(null);

        $this->customerSessionServiceMock->shouldReceive('handleSession')
            ->never();

        $this->subject->handle($requestMock, fn () => true);
    }
}
