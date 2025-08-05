<?php

namespace Tests\Unit\Actions\Appointment;

use App\Actions\Appointment\ShowAppointmentsHistoryAction;
use App\DTO\Appointment\SearchAppointmentsDTO;
use App\Interfaces\Repository\AppointmentRepository;
use App\Models\Account;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\AppointmentData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class ShowAppointmentsHistoryActionTest extends TestCase
{
    use RandomIntTestData;

    protected MockInterface|AppointmentRepository $appointmentRepositoryMock;

    protected Account $accountModel;
    protected ShowAppointmentsHistoryAction $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->accountModel = Account::factory()->makeOne([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]);

        $this->appointmentRepositoryMock = Mockery::mock(AppointmentRepository::class);
        $this->appointmentRepositoryMock
            ->shouldReceive('office')
            ->withArgs([$this->accountModel->office_id])
            ->andReturn($this->appointmentRepositoryMock)
            ->once();

        $this->appointmentRepositoryMock
            ->shouldReceive('withRelated')
            ->withArgs([['documents']])
            ->andReturn($this->appointmentRepositoryMock)
            ->once();

        $this->subject = new ShowAppointmentsHistoryAction($this->appointmentRepositoryMock);
    }

    /**
     * @dataProvider historyDataProvider
     */
    public function test_it_searches_appointments_history(
        array $appointmentsData,
        array $expectedResultData
    ): void {
        $appointmentsCollection = !empty($appointmentsData)
            ? AppointmentData::getTestEntityData(count($appointmentsData), ...$appointmentsData)
            : new Collection();

        $expectedResult = !empty($expectedResultData)
            ? AppointmentData::getTestEntityData(count($expectedResultData), ...$expectedResultData)
            : new Collection();

        $this->appointmentRepositoryMock
            ->shouldReceive('search')
            ->withArgs(
                fn (SearchAppointmentsDTO $dto) => $dto->officeId === $this->accountModel->office_id
                    && $dto->accountNumber === [$this->accountModel->account_number]
                    && $dto->dateEnd <= Carbon::now()
                    && $dto->status === [
                        AppointmentStatus::Completed,
                        AppointmentStatus::NoShow,
                        AppointmentStatus::Rescheduled,
                    ]
            )->once()
            ->andReturn($appointmentsCollection);

        $result = ($this->subject)($this->accountModel);

        self::assertEquals($expectedResult, $result);
    }

    /**
     * @return iterable<int, array<int, array<string, mixed>>>
     */
    public function historyDataProvider(): iterable
    {
        $data = [
            'officeID' => $this->getTestOfficeId(),
            'customerID' => $this->getTestAccountNumber(),
            'date' => Carbon::now()->subDays(random_int(3, 10))->format(AppointmentData::DATE_FORMAT),
            'appointmentID' => $this->getTestAppointmentId(),
        ];

        $completedAppointment = array_merge($data, ['status' => AppointmentStatus::Completed->value]);
        $noShowAppointment = array_merge($data, ['status' => AppointmentStatus::NoShow->value]);
        $rescheduledAppointment = array_merge($data, ['status' => AppointmentStatus::Rescheduled->value]);
        $upcomingAppointment = array_merge($data, [
            'date' => Carbon::now()->format(AppointmentData::DATE_FORMAT),
            'start' => Carbon::now()->addMinutes(random_int(5, 10))->format(AppointmentData::TIME_FORMAT),
            'end' => Carbon::now()->addMinutes(random_int(20, 30))->format(AppointmentData::TIME_FORMAT),
            'status' => AppointmentStatus::Rescheduled->value,
        ]);

        yield [
            [$completedAppointment, $noShowAppointment, $rescheduledAppointment],
            [$completedAppointment, $noShowAppointment, $rescheduledAppointment],
        ];
        yield [
            [$completedAppointment, $noShowAppointment, $upcomingAppointment],
            [$completedAppointment, $noShowAppointment],
        ];
        yield [
            [$upcomingAppointment],
            [],
        ];
    }

    /**
     * @dataProvider spotRepoExceptionsDataProvider
     */
    public function test_it_passes_appointment_repository_exceptions(string $exceptionClass): void
    {
        $this->appointmentRepositoryMock
            ->shouldReceive('search')
            ->andThrow(new $exceptionClass());

        $this->expectException($exceptionClass);

        ($this->subject)($this->accountModel);
    }

    /**
     * @return iterable<int, array<int, string|null>>
     */
    public function spotRepoExceptionsDataProvider(): iterable
    {
        yield [InternalServerErrorHttpException::class];
    }
}
