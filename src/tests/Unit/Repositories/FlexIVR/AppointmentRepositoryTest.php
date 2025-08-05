<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\FlexIVR;

use App\DTO\FlexIVR\Appointment\CreateAppointment;
use App\DTO\FlexIVR\Appointment\RescheduleAppointment;
use App\Enums\FlexIVR\AppointmentType;
use App\Enums\FlexIVR\Source;
use App\Enums\FlexIVR\Window;
use App\Exceptions\Appointment\AppointmentCanNotBeCreatedException;
use App\Exceptions\Appointment\AppointmentCanNotBeRescheduledException;
use App\Exceptions\Appointment\AppointmentSpotAlreadyUsedException;
use App\Exceptions\Appointment\CannotGetCurrentAppointment;
use App\Interfaces\FlexIVRApi\AppointmentRepository as AppointmentRepositoryInterface;
use App\Logging\ApiLogger;
use App\Repositories\FlexIVR\AppointmentRepository;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\InvalidArgumentException;

final class AppointmentRepositoryTest extends RepositoryTest
{
    public function test_current_appointment_throw_exception_on_network_error(): void
    {
        $clientMock = $this->createMock(HttpClient::class);
        $clientMock
            ->expects(self::once())
            ->method('get')
            ->withAnyParameters()
            ->willThrowException(new InvalidArgumentException('Network error'));

        $configMock = $this->getConfigMock();
        $loggerMock = $this->createMock(ApiLogger::class);
        $loggerMock->expects(self::once())->method('logExternalRequest');
        $loggerMock->expects(self::never())->method('logExternalResponse');

        $repository = new AppointmentRepository($clientMock, $configMock, $loggerMock);

        $this->expectException(CannotGetCurrentAppointment::class);

        $repository->getCurrentAppointment($this->getTestAccountNumber());
    }

    public function test_current_appointment_throw_exception_on_failure_response(): void
    {
        $clientMock = $this->mockGetHttpRequest(
            'https://example.com/appointment/current',
            [
                'customerID' => $this->getTestAccountNumber(),
                'allowReservice' => AppointmentRepositoryInterface::FLEX_IVR_REQUEST_ALLOW_RESERVICE_OPTION_VALUE,
                'executionSID' => '',
            ],
            '{"success":false,"message":"TEST"}',
        );

        $configMock = $this->getConfigMock();
        $loggerMock = $this->createMock(ApiLogger::class);
        $loggerMock->expects(self::once())->method('logExternalRequest');
        $loggerMock->expects(self::once())->method('logExternalResponse');

        $repository = new AppointmentRepository($clientMock, $configMock, $loggerMock);

        $this->expectException(CannotGetCurrentAppointment::class);

        $repository->getCurrentAppointment($this->getTestAccountNumber());
    }

    /**
     * @dataProvider provideCurrentAppointmentRequestOptions
     */
    public function test_current_appointment_returns_appointment(
        array $requestData,
        bool $allowReservice
    ): void {
        $clientMock = $this->mockGetHttpRequest(
            'https://example.com/appointment/current',
            $requestData,
            sprintf(
                '{"success":true,"message":"TEST","appointment":{"appointmentID":%d}}',
                $this->getTestAppointmentId(),
            )
        );

        $configMock = $this->getConfigMock();
        $loggerMock = $this->createMock(ApiLogger::class);
        $loggerMock->expects(self::once())->method('logExternalRequest');
        $loggerMock->expects(self::once())->method('logExternalResponse');

        $repository = new AppointmentRepository($clientMock, $configMock, $loggerMock);

        $appointment = $repository->getCurrentAppointment($requestData['customerID'], $allowReservice);

        $this->assertSame($this->getTestAppointmentId(), $appointment->appointmentID);
    }

    public function test_create_appointment_throw_exception_on_network_error(): void
    {
        $clientMock = $this->createMock(HttpClient::class);
        $clientMock
            ->expects(self::once())
            ->method('put')
            ->withAnyParameters()
            ->willThrowException(new InvalidArgumentException('Network error'));

        $configMock = $this->getConfigMock();
        $loggerMock = $this->createMock(ApiLogger::class);

        $repository = new AppointmentRepository($clientMock, $configMock, $loggerMock);
        $loggerMock->expects(self::once())->method('logExternalRequest');
        $loggerMock->expects(self::never())->method('logExternalResponse');

        $this->expectException(AppointmentCanNotBeCreatedException::class);

        $repository->createAppointment($this->getCreateAppointmentDto());
    }

    public function test_create_appointment_throw_exception_on_failure_response(): void
    {
        $dto = $this->getCreateAppointmentDto();

        $clientMock = $this->mockPutHttpRequest(
            'https://example.com/appointment/createV2',
            $dto->toArray(),
            '{"success": false, "message": "Spot 27266694 does not belong to office 1."}',
        );

        $configMock = $this->getConfigMock();
        $loggerMock = $this->createMock(ApiLogger::class);
        $loggerMock->expects(self::once())->method('logExternalRequest');
        $loggerMock->expects(self::once())->method('logExternalResponse');

        $repository = new AppointmentRepository($clientMock, $configMock, $loggerMock);

        $this->expectException(AppointmentCanNotBeCreatedException::class);

        $repository->createAppointment($dto);
    }

    public function test_create_appointment_throw_already_used_exception_on_failure_response(): void
    {
        $dto = $this->getCreateAppointmentDto();

        $clientMock = $this->mockPutHttpRequest(
            'https://example.com/appointment/createV2',
            $dto->toArray(),
            '{"success": false, "message": "Spot 112056551 is directly occupied by appointment 33578565."}',
        );

        $configMock = $this->getConfigMock();
        $loggerMock = $this->createMock(ApiLogger::class);
        $loggerMock->expects(self::once())->method('logExternalRequest');
        $loggerMock->expects(self::once())->method('logExternalResponse');

        $repository = new AppointmentRepository($clientMock, $configMock, $loggerMock);

        $this->expectException(AppointmentSpotAlreadyUsedException::class);

        $repository->createAppointment($dto);
    }

    public function test_create_appointment_returns_appointment_id(): void
    {
        $dto = $this->getCreateAppointmentDto();

        $clientMock = $this->mockPutHttpRequest(
            'https://example.com/appointment/createV2',
            $dto->toArray(),
            sprintf(
                '{"success": true, "message": "Success!", "appointmentID": "%d", "executionSID": ""}',
                $this->getTestAppointmentId(),
            )
        );

        $configMock = $this->getConfigMock();
        $loggerMock = $this->createMock(ApiLogger::class);
        $loggerMock->expects(self::once())->method('logExternalRequest');
        $loggerMock->expects(self::once())->method('logExternalResponse');

        $repository = new AppointmentRepository($clientMock, $configMock, $loggerMock);

        $this->assertSame($this->getTestAppointmentId(), $repository->createAppointment($dto));
    }

    private function getCreateAppointmentDto(): CreateAppointment
    {
        return new CreateAppointment(
            officeId: $this->getTestOfficeId(),
            accountNumber: $this->getTestAccountNumber(),
            subscriptionId: $this->getTestSubscriptionId(),
            spotId: $this->getTestSpotId(),
            window: random_int(0, 1) === 1 ? Window::AM : Window::PM,
            appointmentType: AppointmentType::RESERVICE,
            isAroSpot: random_int(0, 1) === 1,
            requestingSource: Source::CUSTOMER_PORTAL,
        );
    }

    public function test_reschedule_appointment_throw_exception_on_network_error(): void
    {
        $clientMock = $this->createMock(HttpClient::class);
        $clientMock
            ->expects(self::once())
            ->method('put')
            ->withAnyParameters()
            ->willThrowException(new InvalidArgumentException('Network error'));

        $configMock = $this->getConfigMock();
        $loggerMock = $this->createMock(ApiLogger::class);
        $loggerMock->expects(self::once())->method('logExternalRequest');
        $loggerMock->expects(self::never())->method('logExternalResponse');

        $repository = new AppointmentRepository($clientMock, $configMock, $loggerMock);

        $this->expectException(AppointmentCanNotBeRescheduledException::class);

        $repository->rescheduleAppointment($this->getRescheduleAppointmentDto());
    }

    public function test_reschedule_appointment_throw_exception_on_failure_response(): void
    {
        $dto = $this->getRescheduleAppointmentDto();

        $clientMock = $this->mockPutHttpRequest(
            'https://example.com/appointment/rescheduleV2',
            $dto->toArray(),
            '{"success": false, "message": "Spot 27266694 does not belong to office 1."}',
        );

        $configMock = $this->getConfigMock();
        $loggerMock = $this->createMock(ApiLogger::class);
        $loggerMock->expects(self::once())->method('logExternalRequest');
        $loggerMock->expects(self::once())->method('logExternalResponse');

        $repository = new AppointmentRepository($clientMock, $configMock, $loggerMock);

        $this->expectException(AppointmentCanNotBeRescheduledException::class);

        $repository->rescheduleAppointment($dto);
    }

    public function test_reschedule_appointment_throw_spot_used_exception_on_failure_response(): void
    {
        $dto = $this->getRescheduleAppointmentDto();

        $clientMock = $this->mockPutHttpRequest(
            'https://example.com/appointment/rescheduleV2',
            $dto->toArray(),
            '{"success": false, "message": "Could not create appointment, please try again. Spot 108180321 is directly occupied by appointment 33120265.", "executionSID": ""}',
        );

        $configMock = $this->getConfigMock();
        $loggerMock = $this->createMock(ApiLogger::class);
        $loggerMock->expects(self::once())->method('logExternalRequest');
        $loggerMock->expects(self::once())->method('logExternalResponse');

        $repository = new AppointmentRepository($clientMock, $configMock, $loggerMock);

        $this->expectException(AppointmentSpotAlreadyUsedException::class);

        $repository->rescheduleAppointment($dto);
    }

    public function test_reschedule_appointment_returns_appointment_id(): void
    {
        $dto = $this->getRescheduleAppointmentDto();

        $clientMock = $this->mockPutHttpRequest(
            'https://example.com/appointment/rescheduleV2',
            $dto->toArray(),
            sprintf(
                '{"success": true, "message": "Success! New appointment scheduled (ID: %d) and previous appointment marked as Cancelled (ID: %d).", "appointmentID": "%d", "executionSID": 987654321}',
                $this->getTestAppointmentId() + 1,
                $this->getTestAppointmentId(),
                $this->getTestAppointmentId() + 1,
            )
        );

        $configMock = $this->getConfigMock();
        $loggerMock = $this->createMock(ApiLogger::class);
        $loggerMock->expects(self::once())->method('logExternalRequest');
        $loggerMock->expects(self::once())->method('logExternalResponse');

        $repository = new AppointmentRepository($clientMock, $configMock, $loggerMock);

        $this->assertSame($this->getTestAppointmentId() + 1, $repository->rescheduleAppointment($dto));
    }

    protected function provideCurrentAppointmentRequestOptions(): iterable
    {
        yield 'current_appointment_allow_reservice' => [
            [
                'customerID' => $this->getTestAccountNumber(),
                'allowReservice' => AppointmentRepositoryInterface::FLEX_IVR_REQUEST_ALLOW_RESERVICE_OPTION_VALUE,
                'executionSID' => '',
            ],
            true,
        ];

        yield 'current_appointment_disallow_reservice' => [
            [
                'customerID' => $this->getTestAccountNumber(),
                'executionSID' => '',
            ],
            false
        ];
    }

    private function getRescheduleAppointmentDto(): RescheduleAppointment
    {
        return new RescheduleAppointment(
            officeId: $this->getTestOfficeId(),
            accountNumber: $this->getTestAccountNumber(),
            subscriptionId: $this->getTestSubscriptionId(),
            spotId: $this->getTestSpotId(),
            appointmentId: $this->getTestAppointmentId(),
            appointmentType: $this->getTestServiceTypeId(),
            window: random_int(0, 1) === 1 ? Window::AM : Window::PM,
            isAroSpot: random_int(0, 1) === 1,
            requestingSource: Source::CUSTOMER_PORTAL,
        );
    }
}
