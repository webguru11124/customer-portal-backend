<?php

namespace Tests\Unit\Http\Middleware;

use App\Exceptions\Admin\ApiKeyMissingException;
use App\Helpers\ApiKey;
use App\Http\Middleware\RequireKey;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class RequireKeyTest extends TestCase
{
    protected $verifierMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->verifierMock = Mockery::mock(ApiKey::class);
        $this->instance(ApiKey::class, $this->verifierMock);
    }

    public function test_handle_verifies_valid_request()
    {
        $this->verifierMock
            ->shouldReceive('validateKeyPermission')
            ->with('Test', 'test.name')
            ->andReturnTrue()
            ->once();
        $this->prepRoute();

        $middleware = new RequireKey($this->verifierMock);
        $middleware->handle($this->getRequest(), function ($req) {
            self::assertInstanceOf(Request::class, $req);
        });
    }

    public function test_handle_returns_unauthorized_for_request_without_authorization_header()
    {
        $this->verifierMock
            ->shouldReceive('validateKeyPermission')
            ->with('', 'test.name')
            ->andReturnFalse()
            ->once();

        $this->prepRoute();

        $middleware = new RequireKey($this->verifierMock);

        $response = $middleware->handle(new Request(content: 'Request'), fn () => true);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->status());
    }

    public function test_handle_returns_unauthorized_for_invalid_key()
    {
        $this->verifierMock
            ->shouldReceive('validateKeyPermission')
            ->with('Test', 'test.name')
            ->andReturnFalse()
            ->once();

        $this->prepRoute();

        $middleware = new RequireKey($this->verifierMock);

        $response = $middleware->handle($this->getRequest(), fn () => true);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->status());
    }

    public function test_handle_returns_unauthorized_for_invalid_settings_and_logs_error()
    {
        Log::spy();
        $this->verifierMock
            ->shouldReceive('validateKeyPermission')
            ->with('Test', 'test.name')
            ->andThrow(new ApiKeyMissingException())
            ->once();

        $this->prepRoute();

        $middleware = new RequireKey($this->verifierMock);

        $response = $middleware->handle($this->getRequest(), fn () => true);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->status());
        Log::shouldHaveReceived('error')->once();
    }

    protected function getRequest()
    {
        $request = new Request(content: 'Request');
        $request->headers->set('Authorization', 'Bearer Test');

        return $request;
    }

    private function prepRoute()
    {
        Route::shouldReceive('currentRouteName')
            ->andReturn('test.name')
            ->once();
        Route::shouldReceive('getRoutes')
            ->andReturn(new RouteCollection());
    }
}
