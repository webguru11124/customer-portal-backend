<?php

namespace Tests\Unit\Actions\Appointment;

use App\Actions\Appointment\UpdateAppointmentAction;
use App\DTO\Appointment\UpdateAppointmentDTO;
use App\DTO\Check;
use App\Events\Appointment\AppointmentRescheduled;
use App\Exceptions\Appointment\AppointmentCanNotBeRescheduledException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Interfaces\Repository\AppointmentRepository;
use App\Interfaces\Repository\EmployeeRepository;
use App\Interfaces\Repository\SpotRepository;
use App\Models\Account;
use App\Models\External\AppointmentModel;
use App\Models\External\ServiceTypeModel;
use App\Models\External\SpotModel;
use App\Services\AppointmentService;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\AppointmentData;
use Tests\Data\ServiceTypeData;
use Tests\Data\SpotData;
use Tests\TestCase;
use Tests\Traits\GenerateDate;
use Tests\Traits\MockFindAppointment;
use Tests\Traits\MockFindCxpScheduler;
use Tests\Traits\MockFindSpot;
use Tests\Traits\RandomIntTestData;
use Throwable;

class UpdateAppointmentActionTest extends TestCase
{
    use RandomIntTestData;
    use GenerateDate;
    use MockFindAppointment;
    use MockFindSpot;
    use MockFindCxpScheduler;

    private const DURATION_STANDARD = 29;
    private const TEST_NOTES = 'Test notes';

    protected MockInterface|AppointmentService $appointmentServiceMock;
    protected MockInterface|AppointmentRepository $appointmentRepositoryMock;
    protected MockInterface|SpotRepository $spotRepositoryMock;
    protected MockInterface|EmployeeRepository $employeeRepositoryMock;

    protected Account $accountModel;
    protected UpdateAppointmentAction $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->appointmentServiceMock = Mockery::mock(AppointmentService::class)->makePartial();
        $this->appointmentRepositoryMock = Mockery::mock(AppointmentRepository::class);
        $this->spotRepositoryMock = Mockery::mock(SpotRepository::class);
        $this->employeeRepositoryMock = Mockery::mock(EmployeeRepository::class);

        $this->subject = new UpdateAppointmentAction(
            $this->appointmentServiceMock,
            $this->appointmentRepositoryMock,
            $this->spotRepositoryMock,
            $this->employeeRepositoryMock,
        );

        $this->accountModel = Account::factory()->makeOne([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]);
    }

    protected function getAppointmentRepositoryMock(): MockInterface|AppointmentRepository
    {
        return $this->appointmentRepositoryMock;
    }

    protected function getSpotRepositoryMock(): MockInterface|SpotRepository
    {
        return $this->spotRepositoryMock;
    }

    /**
     * @dataProvider updateAppointmentDataProvider
     */
    public function test_it_updates_appointment(
        string $spotTime,
        string $startTime,
        string $endTime,
        int|null $employeeId
    ): void {
        /** @var AppointmentModel $appointment */
        $appointment = AppointmentData::getTestEntityData()->first();
        /** @var ServiceTypeModel $serviceType */
        $serviceType = ServiceTypeData::getTestEntityDataOfTypes($appointment->serviceTypeId)->first();
        $appointment->setRelated('serviceType', $serviceType);

        $this->mockFindAppointment($appointment->id, $appointment);

        $this->appointmentServiceMock
            ->shouldReceive('canRescheduleAppointment')
            ->withArgs([$appointment])
            ->andReturn(Check::true())
            ->once();

        $date = Carbon::now()->addDay()->format(SpotData::DATE_FORMAT);

        /** @var SpotModel $spot */
        $spot = SpotData::getTestEntityData(1, [
            'date' => $date,
            'start' => $spotTime,
        ])->first();

        $this->mockFindSpot($this->accountModel->office_id, $spot->id, $spot);

        $this->appointmentServiceMock
            ->shouldReceive('canAssignSpotToAppointment')
            ->withArgs([$spot])
            ->andReturn(Check::true())
            ->once();

        $this->appointmentServiceMock
            ->shouldReceive('calculateAppointmentDuration')
            ->withArgs([$serviceType])
            ->andReturn(self::DURATION_STANDARD)
            ->once();

        $this->mockFindCxpScheduler(
            $this->employeeRepositoryMock,
            $this->accountModel->office_id,
            $employeeId
        );

        $this->appointmentRepositoryMock
            ->shouldReceive('updateAppointment')
            ->withArgs(
                fn (UpdateAppointmentDTO $dto) => $dto->officeId === $this->accountModel->office_id
                    && $dto->appointmentId === $appointment->id
                    && $dto->spotId === null
                    && $dto->start->format('Y-m-d H:i:s') === "$date $startTime"
                    && $dto->end->format('Y-m-d H:i:s') === "$date $endTime"
                    && $dto->duration === self::DURATION_STANDARD
                    && $dto->notes === self::TEST_NOTES
                    && $dto->employeeId === $employeeId
            )
            ->andReturn($appointment->id)
            ->once();

        Event::fake();

        $result = ($this->subject)($this->accountModel, $appointment->id, $spot->id, self::TEST_NOTES);

        Event::assertDispatched(AppointmentRescheduled::class);

        self::assertEquals($appointment->id, $result);
    }

    /**
     * @return iterable<string, array<int, mixed>>
     */
    public function updateAppointmentDataProvider(): iterable
    {
        yield 'AM scheduling' => [
            '09:00:00',
            '08:00:00',
            '13:00:00',
            $this->getTestEmployeeId(),
        ];
        yield 'PM scheduling' => [
            '16:00:00',
            '13:00:00',
            '20:00:00',
            null,
        ];
    }

    public function test_it_throws_appointment_can_not_be_rescheduled_exception_if_can_not_reschedule(): void
    {
        /** @var AppointmentModel $appointment */
        $appointment = AppointmentData::getTestEntityData()->first();

        $this->mockFindAppointment($appointment->id, $appointment);

        $this->appointmentServiceMock
            ->shouldReceive('canRescheduleAppointment')
            ->andReturn(Check::false('reason'));
        Event::fake();

        $this->expectException(AppointmentCanNotBeRescheduledException::class);

        ($this->subject)(
            $this->accountModel,
            $appointment->id,
            $this->getTestSpotId(),
            self::TEST_NOTES
        );
        Event::assertNotDispatched(AppointmentRescheduled::class);
    }

    public function test_it_throws_appointment_can_not_be_rescheduled_exception_if_can_not_assign_spot(): void
    {
        /** @var AppointmentModel $appointment */
        $appointment = AppointmentData::getTestEntityData()->first();

        $this->mockFindAppointment($appointment->id, $appointment);

        $this->appointmentServiceMock
            ->shouldReceive('canRescheduleAppointment')
            ->andReturn(Check::true());

        /** @var SpotModel $spot */
        $spot = SpotData::getTestEntityData()->first();
        $this->mockFindSpot($this->accountModel->office_id, $spot->id, $spot);

        $this->appointmentServiceMock
            ->shouldReceive('canAssignSpotToAppointment')
            ->andReturn(Check::false('reason'));
        Event::fake();

        $this->expectException(AppointmentCanNotBeRescheduledException::class);

        ($this->subject)($this->accountModel, $appointment->id, $spot->id);
        Event::assertNotDispatched(AppointmentRescheduled::class);
    }

    /**
     * @dataProvider appointmentRepoExceptionsDataProvider
     */
    public function test_it_passes_appointment_repository_exceptions(string $exceptionClass): void
    {
        /** @var Throwable $exception */
        $exception = new $exceptionClass();

        $this->mockFindAppointment($this->getTestAppointmentId(), $exception);
        Event::fake();

        $this->expectException($exceptionClass);

        ($this->subject)($this->accountModel, $this->getTestAppointmentId(), $this->getTestSpotId());
        Event::assertNotDispatched(AppointmentRescheduled::class);
    }

    /**
     * @return iterable<int, array<int, string|null>>
     */
    public function appointmentRepoExceptionsDataProvider(): iterable
    {
        yield [InternalServerErrorHttpException::class];
        yield [EntityNotFoundException::class];
    }

    /**
     * @dataProvider spotRepoExceptionsDataProvider
     */
    public function test_it_passes_spot_repository_exceptions(string $exceptionClass): void
    {
        /** @var AppointmentModel $appointment */
        $appointment = AppointmentData::getTestEntityData()->first();

        $this->mockFindAppointment($appointment->id, $appointment);

        $this->appointmentServiceMock
            ->shouldReceive('canRescheduleAppointment')
            ->andReturn(Check::true());

        $this->mockFindSpot($this->accountModel->office_id, $this->getTestSpotId(), new $exceptionClass());
        Event::fake();

        $this->expectException($exceptionClass);

        ($this->subject)($this->accountModel, $appointment->id, $this->getTestSpotId());
        Event::assertNotDispatched(AppointmentRescheduled::class);
    }

    /**
     * @return iterable<int, array<int, string|null>>
     */
    public function spotRepoExceptionsDataProvider(): iterable
    {
        yield [InternalServerErrorHttpException::class];
        yield [EntityNotFoundException::class];
    }
}
