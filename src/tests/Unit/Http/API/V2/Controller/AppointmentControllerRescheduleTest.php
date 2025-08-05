<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Actions\Appointment\RescheduleAppointmentInFlexIVRAction;
use App\Enums\FlexIVR\Window;
use App\Enums\Resources;
use App\Exceptions\Account\AccountNotFoundException;
use App\Exceptions\Appointment\AppointmentCanNotBeCreatedException;
use App\Exceptions\Appointment\AppointmentCanNotBeRescheduledException;
use App\Exceptions\Appointment\AppointmentSpotAlreadyUsedException;
use App\Exceptions\Appointment\CannotGetCurrentAppointment;
use App\Exceptions\Entity\EntityNotFoundException;
use Aptive\Component\Http\HttpStatus;
use Exception;
use Tests\Traits\TestAuthorizationMiddleware;
use Throwable;

class AppointmentControllerRescheduleTest extends AppointmentController
{
    use TestAuthorizationMiddleware;

    public function test_reschedule_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(fn () => $this->patchJson(
            route('api.v2.customer.appointments.reschedule', [
                'accountNumber' => $this->getTestAccountNumber(),
                'appointmentId' => $this->getTestAppointmentId(),
            ]),
        ));
    }

    public function test_reschedule_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();
        $this->patchJson(
            route('api.v2.customer.appointments.reschedule', [
                'accountNumber' => $this->getTestAccountNumber(),
                'appointmentId' => $this->getTestAppointmentId(),
            ]),
        )->assertNotFound();
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws Exception
     */
    public function test_reschedule_creates_appointment(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $requestData = $this->getRandomAppointmentRequestData();
        $appointment = $this->getAppointment();

        $actionMock = $this->createMock(RescheduleAppointmentInFlexIVRAction::class);
        $actionMock
            ->expects(self::once())
            ->method('__invoke')
            ->with(
                $this->getTestAccountNumber(),
                $this->getTestAppointmentId(),
                $this->getTestSpotId(),
                Window::from($requestData['window']),
                $requestData['is_aro_spot'],
                $requestData['notes'],
            )
            ->willReturn($appointment);

        $this->instance(RescheduleAppointmentInFlexIVRAction::class, $actionMock);

        $response = $this->patchJson(
            route('api.v2.customer.appointments.reschedule', [
                'accountNumber' => $this->getTestAccountNumber(),
                'appointmentId' => $this->getTestAppointmentId(),
            ]),
            $requestData,
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', (string) $appointment->id)
            ->assertJsonPath('data.type', Resources::APPOINTMENT->value);
    }

    /**
     * @dataProvider rescheduleExceptionProvider
     *
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function test_reschedule_handles_exceptions(Throwable $exception, int $expectedStatusCode): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $requestData = $this->getRandomAppointmentRequestData();

        $actionMock = $this->createMock(RescheduleAppointmentInFlexIVRAction::class);
        $actionMock
            ->expects(self::once())
            ->method('__invoke')
            ->with(
                $this->getTestAccountNumber(),
                $this->getTestAppointmentId(),
                $this->getTestSpotId(),
                Window::from($requestData['window']),
                $requestData['is_aro_spot'],
                $requestData['notes'],
            )
            ->willThrowException($exception);

        $this->instance(RescheduleAppointmentInFlexIVRAction::class, $actionMock);

        $response = $this->patchJson(
            route(
                'api.v2.customer.appointments.reschedule',
                [
                    'accountNumber' => $this->getTestAccountNumber(),
                    'appointmentId' => $this->getTestAppointmentId(),
                ]
            ),
            $requestData
        );

        $response->assertStatus($expectedStatusCode);
    }

    /**
     * @return iterable<string, array{exception: Throwable, expectedStatusCode: int}>
     */
    public static function rescheduleExceptionProvider(): iterable
    {
        yield 'Appointment cannot be created' => [
            'exception' => new AppointmentCanNotBeCreatedException('Test'),
            'expectedStatusCode' => HttpStatus::INTERNAL_SERVER_ERROR,
        ];
        yield 'Cannot get current appointment' => [
            'exception' => new CannotGetCurrentAppointment('Test'),
            'expectedStatusCode' => HttpStatus::INTERNAL_SERVER_ERROR,
        ];
        yield 'Account not found' => [
            'exception' => new AccountNotFoundException('Test'),
            'expectedStatusCode' => HttpStatus::NOT_FOUND,
        ];
        yield 'Appointment not found' => [
            'exception' => new EntityNotFoundException('Test'),
            'expectedStatusCode' => HttpStatus::NOT_FOUND,
        ];
        yield 'Generic exception' => [
            'exception' => new Exception('Test'),
            'expectedStatusCode' => HttpStatus::INTERNAL_SERVER_ERROR,
        ];
        yield 'Spot already used' => [
            'exception' => new AppointmentSpotAlreadyUsedException(),
            'expectedStatusCode' => HttpStatus::UNPROCESSABLE_ENTITY,
        ];
        yield 'Cannot reschedule initial' => [
            'exception' => new AppointmentCanNotBeRescheduledException('Test'),
            'expectedStatusCode' => HttpStatus::UNPROCESSABLE_ENTITY,
        ];
    }

    /**
     * @dataProvider invalidRequestProvider
     *
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function test_reschedule_handles_validation_errors(array $requestData, string $error): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $actionMock = $this->createMock(RescheduleAppointmentInFlexIVRAction::class);
        $this->instance(RescheduleAppointmentInFlexIVRAction::class, $actionMock);

        $response = $this->patchJson(
            route(
                'api.v2.customer.appointments.reschedule',
                [
                    'accountNumber' => $this->getTestAccountNumber(),
                    'appointmentId' => $this->getTestAppointmentId(),
                ]
            ),
            $requestData
        );

        $response->assertUnprocessable()
            ->assertJsonPath('message', $error);
    }
}
