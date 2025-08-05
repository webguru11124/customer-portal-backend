<?php

namespace Tests\Unit\Http\API\V1\Controller;

use App\Actions\ResendEmailVerificationAction;
use App\Exceptions\Auth0\ApiRequestFailedException;
use Illuminate\Testing\TestResponse;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\TestAuthorizationMiddleware;

class EmailVerificationControllerTest extends ApiTestCase
{
    use RandomIntTestData;
    use TestAuthorizationMiddleware;

    protected const ROUTE = 'api.resend-verification-email';
    public MockInterface|ResendEmailVerificationAction $resendEmailActionMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->resendEmailActionMock = Mockery::mock(ResendEmailVerificationAction::class);
        $this->instance(ResendEmailVerificationAction::class, $this->resendEmailActionMock);
    }

    public function test_it_forbids_unauthorized_access(): void
    {
        $this->checkThatActionWithoutAuthenticationReturnsForbidden(fn () => $this->getPostJsonResponse());
    }

    public function test_it_sends_email(): void
    {
        $user = $this->createAndLogInAuth0User();
        $this->resendEmailActionMock
            ->shouldReceive('__invoke')
            ->with($user->external_id)
            ->andReturnNull()
            ->once();

        $this->getPostJsonResponse()
            ->assertNoContent();
    }

    public function test_it_returns_error_on_exception(): void
    {
        $user = $this->createAndLogInAuth0User();
        $this->resendEmailActionMock
            ->shouldReceive('__invoke')
            ->with($user->external_id)
            ->andThrow(new ApiRequestFailedException())
            ->once();

        $this->getPostJsonResponse()
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    protected function getPostJsonResponse(): TestResponse
    {
        return $this->postJson(route(self::ROUTE));
    }
}
