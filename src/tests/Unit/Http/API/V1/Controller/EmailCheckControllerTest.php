<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V1\Controller;

use App\Actions\CheckEmailAction;
use App\DTO\CheckEmailResponseDTO;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Customers\CustomerStatus;
use Illuminate\Testing\TestResponse;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;

class EmailCheckControllerTest extends ApiTestCase
{
    private const EMAIL = 'test@test.com';

    protected MockInterface|CheckEmailAction $checkEmailActionMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->checkEmailActionMock = Mockery::mock(CheckEmailAction::class);

        $this->instance(CheckEmailAction::class, $this->checkEmailActionMock);
    }

    public function test_check_returns_email_status(): void
    {
        $dto = new CheckEmailResponseDTO(
            exists: true,
            hasLoggedIn: false,
            hasRegistered: true,
            completedInitialService: false,
            status: CustomerStatus::Active
        );

        $this->checkEmailActionMock
            ->shouldReceive('__invoke')
            ->with(self::EMAIL, 'Auth0')
            ->once()
            ->andReturn($dto);

        $this->getCheckJsonResponse(self::EMAIL)
            ->assertOk()
            ->assertExactJson([
                'exists' => $dto->exists,
                'has_logged_in' => $dto->hasLoggedIn,
                'has_registered' => $dto->hasRegistered,
                'completed_initial_service' => $dto->completedInitialService,
                'status' => $dto->status
            ]);
    }

    protected function getCheckJsonResponse($email): TestResponse
    {
        return $this->postJson(route('api.emailcheck'), ['email' => $email]);
    }

    /**
     * @dataProvider provideInvalidEmailData
     */
    public function test_check_returns_validation_error($email): void
    {
        $this->getCheckJsonResponse($email)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The email must be a valid email address.');
    }

    public function provideInvalidEmailData(): array
    {
        return [
            ['email' => 'notexisttest.com'],
            ['email' => false],
            ['email' => 123],
        ];
    }

    public function test_check_handles_fatal_error(): void
    {
        $this->checkEmailActionMock
            ->shouldReceive('__invoke')
            ->with(self::EMAIL, 'Auth0')
            ->andThrow(new InternalServerErrorHttpException())
            ->once();

        $this->getCheckJsonResponse(self::EMAIL)
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJsonPath('errors.0.title', '500 Internal Server Error');
    }
}
