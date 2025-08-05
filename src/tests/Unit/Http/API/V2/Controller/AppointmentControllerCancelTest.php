<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Exceptions\Appointment\AppointmentNotCancelledException;
use App\Models\Account;
use Symfony\Component\HttpFoundation\Response;
use Tests\Traits\TestAuthorizationMiddleware;
use Throwable;

class AppointmentControllerCancelTest extends AppointmentController
{
    use TestAuthorizationMiddleware;

    private function getCancelRoute(): string
    {
        return route('api.v2.customer.appointments.cancel', [
            'accountNumber' => $this->getTestAccountNumber(),
            'appointmentId' => $this->getTestAppointmentId(),
        ]);
    }
    public function test_cancel_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();

        $this
            ->deleteJson($this->getCancelRoute())
            ->assertNotFound();
    }

    public function test_cancel_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(fn () => $this->deleteJson($this->getCancelRoute()));
    }

    public function test_cancel_appointment_cancels_appointment(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->cancelAppointmentActionMock
            ->shouldReceive('__invoke')
            ->withArgs(
                fn (Account $account, int $appointmentId) => $account->account_number === $this->getTestAccountNumber()
                    && $appointmentId === $this->getTestAppointmentId()
            )
            ->once();

        $this
            ->deleteJson($this->getCancelRoute())
            ->assertNoContent();
    }

    /**
     * @dataProvider appointmentCancellationExceptionProvider
     */
    public function test_cancel_appointment_shows_cancellation_error(Throwable $exception, int $responseCode): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $this->cancelAppointmentActionMock
            ->shouldReceive('__invoke')
            ->withArgs(
                fn (Account $account, int $appointmentId) => $account->account_number === $this->getTestAccountNumber()
                    && $appointmentId === $this->getTestAppointmentId()
            )
            ->once()
            ->andThrow($exception);

        $this
            ->deleteJson($this->getCancelRoute())
            ->assertStatus($responseCode);
    }

    public function appointmentCancellationExceptionProvider(): array
    {
        return [
            'Cancellation failed' => [
                new AppointmentNotCancelledException(),
                Response::HTTP_INTERNAL_SERVER_ERROR,
            ],
        ];
    }
}
