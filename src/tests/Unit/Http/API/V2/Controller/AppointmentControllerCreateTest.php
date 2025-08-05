<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Enums\Resources;
use App\Models\Account;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Tests\Traits\TestAuthorizationMiddleware;

class AppointmentControllerCreateTest extends AppointmentController
{
    use TestAuthorizationMiddleware;

    private function getCreateRoute(): string
    {
        return route('api.v2.customer.appointments.create', ['accountNumber' => $this->getTestAccountNumber()]);
    }

    public function test_create_appointment(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->createAppointmentActionMock
            ->shouldReceive('__invoke')
            ->withArgs(
                fn (Account $account, int $spotId, string $notes) => $account->account_number === $this->getTestAccountNumber()
                    && $spotId === $this->getTestSpotId()
                    && $notes === self::NOTES
            )
            ->once()
            ->andReturn(self::CREATED_APPOINTMENT_ID);

        $requestData = [
            'spotId' => $this->getTestSpotId(),
            'notes' => self::NOTES,
        ];

        $response = $this
            ->putJson($this->getCreateRoute(), $requestData);

        $response->assertCreated();
        $response->assertExactJson($this->getResourceCreatedExpectedResponse(
            Resources::APPOINTMENT->value,
            self::CREATED_APPOINTMENT_ID
        ));
    }

    public function test_create_throws_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->accountServiceMock
            ->shouldReceive('getOfficeIdByAccountNumber')
            ->andThrow(Exception::class);

        $response = $this
            ->putJson($this->getCreateRoute(), [
                'spotId' => 45234523,
                'notes' => self::NOTES,
            ]);

        $this->assertErrorResponse($response, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function test_create_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();

        $this->putJson(
            $this->getCreateRoute(),
            [
                'spotId' => $this->getTestSpotId(),
                'notes' => self::NOTES,
            ]
        )->assertNotFound();

    }
    public function test_create_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(fn () => $this->putJson(
            $this->getCreateRoute(),
            [
                'spotId' => $this->getTestSpotId(),
                'notes' => self::NOTES,
            ]
        ));
    }
}
