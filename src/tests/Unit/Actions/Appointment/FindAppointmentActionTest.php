<?php

namespace Tests\Unit\Actions\Appointment;

use App\Actions\Appointment\FindAppointmentAction;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Interfaces\Repository\AppointmentRepository;
use App\Models\Account;
use App\Models\External\AppointmentModel;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\AppointmentData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class FindAppointmentActionTest extends TestCase
{
    use RandomIntTestData;

    protected MockInterface|AppointmentRepository $appointmentRepositoryMock;

    protected Account $accountModel;
    protected FindAppointmentAction $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->appointmentRepositoryMock = Mockery::mock(AppointmentRepository::class);

        $this->subject = new FindAppointmentAction(
            $this->appointmentRepositoryMock,
        );

        $this->accountModel = Account::factory()->makeOne([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]);
    }

    public function test_it_finds_appointment(): void
    {
        /** @var AppointmentModel $appointment */
        $appointment = AppointmentData::getTestEntityData()->first();

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
            ->shouldReceive('find')
            ->withArgs([$appointment->id])
            ->andReturn($appointment)
            ->once();

        $result = ($this->subject)($this->accountModel, $appointment->id);

        self::assertSame($appointment, $result);
    }

    /**
     * @dataProvider appointmentRepoExceptionsDataProvider
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
            ->shouldReceive('find')
            ->andThrow(new $exceptionClass())
            ->once();

        $this->expectException($exceptionClass);

        ($this->subject)($this->accountModel, $this->getTestAppointmentId());
    }

    /**
     * @return iterable<int, array<int, string|null>>
     */
    public function appointmentRepoExceptionsDataProvider(): iterable
    {
        yield [InternalServerErrorHttpException::class];
        yield [OfficeNotSetException::class];
        yield [EntityNotFoundException::class];
    }
}
