<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ResendEmailVerificationAction;
use App\Exceptions\Auth0\ApiRequestFailedException;
use App\Interfaces\Auth0\UserService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class ResendEmailVerificationActionTest extends TestCase
{
    private const AUTH0_ID = '6c86e67eaf2dbc0aa5b62b9838';

    protected MockInterface|UserService $userServiceMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->userServiceMock = Mockery::mock(UserService::class);
    }

    public function test_it_resends_email(): void
    {
        $this->userServiceMock->shouldReceive('resendVerificationEmail')
            ->with(self::AUTH0_ID)
            ->andReturnNull()
            ->once();

        $action = new ResendEmailVerificationAction($this->userServiceMock);
        $this->assertNull(($action)(self::AUTH0_ID));
    }

    public function test_it_throws_api_request_failed_exception(): void
    {
        $this->userServiceMock->shouldReceive('resendVerificationEmail')
            ->with(self::AUTH0_ID)
            ->andThrow(ApiRequestFailedException::class)
            ->once();

        $this->expectException(ApiRequestFailedException::class);

        $action = new ResendEmailVerificationAction($this->userServiceMock);
        ($action)(self::AUTH0_ID);
    }
}
