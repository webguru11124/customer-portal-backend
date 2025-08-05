<?php

namespace Tests\Traits;

use App\Services\UserService;
use Closure;
use Illuminate\Foundation\Testing\Concerns\InteractsWithContainer;
use Illuminate\Testing\TestResponse;
use InvalidArgumentException;
use Mockery;
use Symfony\Component\HttpFoundation\Response;

trait TestAuthorizationMiddleware
{
    use AuthorizeAuth0User;
    use InteractsWithContainer;

    private function checkAuthorizationMiddleware(Closure $protectedActionCall): void
    {
        $this->checkThatActionWithoutAuthenticationReturnsForbidden($protectedActionCall);
        $this->checkThatActionWithoutEmailInTokenReturnsUnauthorized($protectedActionCall);
        $this->checkThatActionWithoutVerifiedEmailReturnsUnauthorized($protectedActionCall);
        $this->checkThatActionWithUnverifiedEmailReturnsUnauthorized($protectedActionCall);
        $this->checkThatActionWithoutExistingPestroutesAccountReturnsPreconditionFailed($protectedActionCall);
    }

    private function checkThatActionWithoutAuthenticationReturnsForbidden(Closure $protectedActionCall): void
    {
        $this
            ->performAction($protectedActionCall)
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthorized');
    }

    private function checkThatActionWithoutEmailInTokenReturnsUnauthorized(Closure $protectedActionCall): void
    {
        $this->actingAsAuth0User(['sub' => self::$auth0ExternalId]);

        $this
            ->performAction($protectedActionCall)
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Email is missing from the token');
    }

    private function checkThatActionWithoutVerifiedEmailReturnsUnauthorized(Closure $protectedActionCall): void
    {
        $this->actingAsAuth0User(['sub' => self::$auth0ExternalId, 'email' => self::$auth0Email]);

        $this
            ->performAction($protectedActionCall)
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Email is not verified');
    }

    private function checkThatActionWithUnverifiedEmailReturnsUnauthorized(Closure $protectedActionCall): void
    {
        $this->actingAsAuth0User([
            'sub' => self::$auth0ExternalId,
            'email' => self::$auth0Email,
            'email_verified' => false,
        ]);

        $this
            ->performAction($protectedActionCall)
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Email is not verified');
    }

    private function checkThatActionWithoutExistingPestroutesAccountReturnsPreconditionFailed(
        Closure $protectedActionCall
    ): void {
        $this->actingAsAuth0User([
            'sub' => self::$auth0ExternalId,
            'email' => self::$auth0Email,
            'email_verified' => true,
        ]);

        $userServiceMock = Mockery::mock(UserService::class);
        $userServiceMock
            ->expects('findUserByEmailAndExtId')
            ->withAnyArgs()
            ->once()
            ->andReturn(null);
        $userServiceMock
            ->expects('createOrUpdateUserWithExternalId')
            ->withAnyArgs()
            ->once()
            ->andReturn(null);

        $this->instance(UserService::class, $userServiceMock);

        $this
            ->performAction($protectedActionCall)
            ->assertStatus(Response::HTTP_PRECONDITION_FAILED);
    }

    private function performAction(Closure $protectedActionCall): TestResponse
    {
        $response = $protectedActionCall();

        if (!($response instanceof TestResponse)) {
            throw new InvalidArgumentException(
                sprintf('Action call closure should return instance of \'%s\'', TestResponse::class)
            );
        }

        return $response;
    }
}
