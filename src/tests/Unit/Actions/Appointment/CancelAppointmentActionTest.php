<?php

namespace Tests\Unit\Actions\Appointment;

use App\Actions\Appointment\CancelAppointmentAction;
use App\DTO\Check;
use App\Events\Appointment\AppointmentCanceled;
use App\Exceptions\Appointment\AppointmentCanNotBeCancelled;
use App\Exceptions\Appointment\AppointmentNotCancelledException;
use App\Exceptions\Authorization\UnauthorizedException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Interfaces\Repository\AppointmentRepository;
use App\Models\Account;
use App\Models\External\AppointmentModel;
use App\Services\AppointmentService;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\AppointmentData;
use Tests\TestCase;
use Tests\Traits\MockFindAppointment;
use Tests\Traits\RandomIntTestData;
use Throwable;

class CancelAppointmentActionTest extends TestCase
{
    use RandomIntTestData;
    use MockFindAppointment;

    protected MockInterface|AppointmentService $appointmentServiceMock;
    protected MockInterface|AppointmentRepository $appointmentRepositoryMock;

    protected Account $accountModel;
    protected CancelAppointmentAction $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->appointmentServiceMock = Mockery::mock(AppointmentService::class)->makePartial();
        $this->appointmentRepositoryMock = Mockery::mock(AppointmentRepository::class);

        $this->subject = new CancelAppointmentAction(
            $this->appointmentServiceMock,
            $this->appointmentRepositoryMock
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

    public function test_it_cancels_appointment(): void
    {
        /** @var AppointmentModel $appointment */
        $appointment = AppointmentData::getTestEntityData(
            1,
            ['customerID' => $this->accountModel->account_number]
        )->first();

        $this->mockFindAppointment($appointment->id, $appointment);

        $this->appointmentServiceMock
            ->shouldReceive('canCancelAppointment')
            ->withArgs([$appointment])
            ->andReturn(Check::true())
            ->once();

        $this->appointmentRepositoryMock
            ->shouldReceive('cancelAppointment')
            ->withArgs([$appointment])
            ->once();

        Event::fake();

        ($this->subject)($this->accountModel, $appointment->id);

        Event::assertDispatched(AppointmentCanceled::class);
    }

    public function test_it_throws_exception_when_appointment_does_not_belong_to_given_account(): void
    {
        /** @var AppointmentModel $appointment */
        $appointment = AppointmentData::getTestEntityData(
            1,
            ['customerID' => $this->accountModel->account_number + 1]
        )->first();

        $this->mockFindAppointment($appointment->id, $appointment);

        $this->expectException(UnauthorizedException::class);

        ($this->subject)($this->accountModel, $appointment->id);
    }

    public function test_it_throws_appointment_can_not_be_canceled(): void
    {
        /** @var AppointmentModel $appointment */
        $appointment = AppointmentData::getTestEntityData(
            1,
            ['customerID' => $this->accountModel->account_number]
        )->first();

        $this->mockFindAppointment($appointment->id, $appointment);

        $this->appointmentServiceMock
            ->shouldReceive('canCancelAppointment')
            ->andReturn(Check::false('reason'));

        $this->expectException(AppointmentCanNotBeCancelled::class);

        ($this->subject)($this->accountModel, $appointment->id);
    }

    /**
     * @dataProvider spotRepoExceptionsDataProvider
     */
    public function test_it_passes_appointment_repository_exceptions(string $exceptionClass): void
    {
        /** @var Throwable $exception */
        $exception = new $exceptionClass();

        $this->mockFindAppointment($this->getTestAppointmentId(), $exception);

        $this->expectException($exceptionClass);

        ($this->subject)($this->accountModel, $this->getTestAppointmentId());
    }

    /**
     * @return iterable<int, array<int, string|null>>
     */
    public function spotRepoExceptionsDataProvider(): iterable
    {
        yield [EntityNotFoundException::class];
        yield [AppointmentNotCancelledException::class];
    }
}
