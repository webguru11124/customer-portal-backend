<?php

namespace Tests\Unit\Actions\Appointment;

use App\Actions\Appointment\ShowUpcomingAppointmentsAction;
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

class ShowUpcomingAppointmentsActionTest extends TestCase
{
    use RandomIntTestData;

    protected MockInterface|AppointmentRepository $appointmentRepositoryMock;

    protected Account $accountModel;
    protected ShowUpcomingAppointmentsAction $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->appointmentRepositoryMock = Mockery::mock(AppointmentRepository::class);

        $this->subject = new ShowUpcomingAppointmentsAction(
            $this->appointmentRepositoryMock,
        );

        $this->accountModel = Account::factory()->makeOne([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]);
    }

    /**
     * @dataProvider upcomingDataProvider
     */
    public function test_it_searches_upcoming_appointments(
        Collection $appointmentsCollection,
        int|null $limit,
        Collection $expectedResult
    ): void {
        $this->appointmentRepositoryMock
            ->shouldReceive('office')
            ->withArgs([$this->accountModel->office_id])
            ->andReturn($this->appointmentRepositoryMock)
            ->once();

        $this->appointmentRepositoryMock
            ->shouldReceive('withRelated')
            ->withArgs([['serviceType']])
            ->andReturn($this->appointmentRepositoryMock)
            ->once();

        $this->appointmentRepositoryMock
            ->shouldReceive('getUpcomingAppointments')
            ->withArgs([$this->accountModel->account_number])->once()
            ->andReturn($appointmentsCollection);

        $result = ($this->subject)($this->accountModel, $limit);

        self::assertEquals($expectedResult, $result);
    }

    /**
     * @return iterable<int, array<int, mixed>>
     */
    public function upcomingDataProvider(): iterable
    {
        $data = [
            'officeID' => $this->getTestOfficeId(),
            'customerID' => $this->getTestAccountNumber(),
            'status' => AppointmentStatus::Pending->value,
        ];

        $upcomingAppointment = AppointmentData::getTestData(1, array_merge($data, [
            'date' => Carbon::now(AppointmentData::CUSTOMER_TIME_ZONE)
                ->addDays(random_int(5, 10))
                ->format(AppointmentData::DATE_FORMAT),
        ]))->first();

        $nextAppointment = AppointmentData::getTestData(1, array_merge($data, [
            'date' => Carbon::now(AppointmentData::CUSTOMER_TIME_ZONE)
                ->addDays(random_int(1, 4))
                ->format(AppointmentData::DATE_FORMAT),
        ]))->first();

        yield [
            Collection::make([$upcomingAppointment, $nextAppointment]),
            null,
            Collection::make([$nextAppointment, $upcomingAppointment]),
        ];
        yield [
            Collection::make([$upcomingAppointment, $nextAppointment]),
            0,
            Collection::make([$nextAppointment, $upcomingAppointment]),
        ];
        yield [
            Collection::make([$upcomingAppointment, $nextAppointment]),
            1,
            Collection::make([$nextAppointment]),

        ];
        yield [
            Collection::make([$nextAppointment, $upcomingAppointment]),
            2,
            Collection::make([$nextAppointment, $upcomingAppointment]),
        ];
    }

    /**
     * @dataProvider spotRepoExceptionsDataProvider
     */
    public function test_it_passes_appointment_repository_exceptions(string $exceptionClass): void
    {
        $this->appointmentRepositoryMock
            ->shouldReceive('office')
            ->andReturn($this->appointmentRepositoryMock);

        $this->appointmentRepositoryMock
            ->shouldReceive('withRelated')
            ->andReturn($this->appointmentRepositoryMock);

        $this->appointmentRepositoryMock
            ->shouldReceive('getUpcomingAppointments')
            ->andThrow(new $exceptionClass());

        $this->expectException($exceptionClass);

        ($this->subject)($this->accountModel, 1);
    }

    /**
     * @return iterable<int, array<int, string|null>>
     */
    public function spotRepoExceptionsDataProvider(): iterable
    {
        yield [InternalServerErrorHttpException::class];
    }
}
