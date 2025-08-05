<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Actions\Appointment\CreateAppointmentInFlexIVRAction;
use App\Enums\FlexIVR\Window;
use App\Enums\Resources;
use App\Exceptions\Account\AccountNotFoundException;
use App\Exceptions\Appointment\AppointmentSpotAlreadyUsedException;
use App\Exceptions\Appointment\CannotCreateAppointmentException;
use App\Exceptions\Appointment\CannotResolveAppointmentSubscriptionException;
use App\Exceptions\Entity\EntityNotFoundException;
use Aptive\Component\Http\HttpStatus;
use Exception;
use Tests\Traits\TestAuthorizationMiddleware;
use Throwable;

class AppointmentControllerCreateFlexTest extends AppointmentController
{
    use TestAuthorizationMiddleware;

    public function test_create_flex_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(fn () => $this->postJson(
            route('api.v2.customer.appointments.create.flex', ['accountNumber' => $this->getTestAccountNumber()]),
        ));
    }

    public function test_create_flex_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();

        $this->postJson(
            route('api.v2.customer.appointments.create.flex', ['accountNumber' => $this->getTestAccountNumber()]),
        )->assertNotFound();
    }

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function test_create_flex_creates_appointment(): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $requestData = $this->getRandomAppointmentRequestData();
        $appointment = $this->getAppointment();

        $actionMock = $this->createMock(CreateAppointmentInFlexIVRAction::class);
        $actionMock
            ->expects(self::once())
            ->method('__invoke')
            ->with(
                $this->getTestAccountNumber(),
                $this->getTestSpotId(),
                Window::from($requestData['window']),
                $requestData['is_aro_spot'],
                $requestData['notes'],
            )
            ->willReturn($appointment);

        $this->instance(CreateAppointmentInFlexIVRAction::class, $actionMock);

        $response = $this->postJson(
            route('api.v2.customer.appointments.create.flex', ['accountNumber' => $this->getTestAccountNumber()]),
            $requestData
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.id', (string) $appointment->id)
            ->assertJsonPath('data.type', Resources::APPOINTMENT->value);
    }

    /**
     * @dataProvider createExceptionProvider
     *
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function test_create_flex_handles_exceptions(Throwable $exception, int $expectedStatusCode): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $requestData = $this->getRandomAppointmentRequestData();

        $actionMock = $this->createMock(CreateAppointmentInFlexIVRAction::class);
        $actionMock
            ->expects(self::once())
            ->method('__invoke')
            ->with(
                $this->getTestAccountNumber(),
                $this->getTestSpotId(),
                Window::from($requestData['window']),
                $requestData['is_aro_spot'],
                $requestData['notes'],
            )
            ->willThrowException($exception);

        $this->instance(CreateAppointmentInFlexIVRAction::class, $actionMock);

        $response = $this->postJson(
            route('api.v2.customer.appointments.create.flex', ['accountNumber' => $this->getTestAccountNumber()]),
            $requestData
        );

        $response->assertStatus($expectedStatusCode);
    }

    /**
     * @return iterable<string, array{exception: Throwable, expectedStatusCode: int}>
     */
    public static function createExceptionProvider(): iterable
    {
        yield 'Cannot determine subscription' => [
            'exception' => new CannotResolveAppointmentSubscriptionException('Test'),
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
        yield 'Cannot create initial appointment' => [
            'exception' => new CannotCreateAppointmentException('Test'),
            'expectedStatusCode' => HttpStatus::CONFLICT,
        ];
    }

    /**
     * @dataProvider invalidRequestProvider
     *
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function test_create_handles_validation_errors(array $requestData, string $error): void
    {
        $this->createAndLogInAuth0UserWithAccount();

        $actionMock = $this->createMock(CreateAppointmentInFlexIVRAction::class);
        $this->instance(CreateAppointmentInFlexIVRAction::class, $actionMock);

        $response = $this->postJson(
            route('api.v2.customer.appointments.create', ['accountNumber' => $this->getTestAccountNumber()]),
            $requestData
        );

        $response->assertUnprocessable()
            ->assertJsonPath('message', $error);
    }
}
