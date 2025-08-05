<?php

namespace Tests\Unit\Actions\Appointment;

use App\Actions\Appointment\SearchAppointmentsAction;
use App\DTO\Appointment\SearchAppointmentsDTO;
use App\Interfaces\Repository\AppointmentRepository;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\AppointmentData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class SearchAppointmentActionTest extends TestCase
{
    use RandomIntTestData;

    protected MockInterface|AppointmentRepository $appointmentRepositoryMock;

    protected SearchAppointmentsAction $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->appointmentRepositoryMock = Mockery::mock(AppointmentRepository::class);

        $this->subject = new SearchAppointmentsAction($this->appointmentRepositoryMock);
    }

    public function test_it_searches_appointments(): void
    {
        $appointmentsCollection = AppointmentData::getTestData();

        $searchAppointmentsDto = $this->getSearchAppointmentsDto();

        $this->appointmentRepositoryMock
            ->shouldReceive('office')
            ->withArgs([$searchAppointmentsDto->officeId])
            ->andReturn($this->appointmentRepositoryMock)
            ->once();

        $this->appointmentRepositoryMock
            ->shouldReceive('withRelated')
            ->withArgs([['serviceType']])
            ->andReturn($this->appointmentRepositoryMock)
            ->once();

        $this->appointmentRepositoryMock
            ->shouldReceive('search')
            ->withArgs([$searchAppointmentsDto])
            ->andReturn($appointmentsCollection);

        $result = ($this->subject)($searchAppointmentsDto);

        self::assertSame($appointmentsCollection, $result);
    }

    public function test_it_passes_exceptions(): void
    {
        $this->appointmentRepositoryMock
            ->shouldReceive('office')
            ->andReturn($this->appointmentRepositoryMock);

        $this->appointmentRepositoryMock
            ->shouldReceive('withRelated')
            ->andReturn($this->appointmentRepositoryMock);

        $this->appointmentRepositoryMock
            ->shouldReceive('search')
            ->andThrow(new InternalServerErrorHttpException());

        $this->expectException(InternalServerErrorHttpException::class);

        ($this->subject)($this->getSearchAppointmentsDto());
    }

    private function getSearchAppointmentsDto(): SearchAppointmentsDTO
    {
        return new SearchAppointmentsDTO(
            officeId: $this->getTestOfficeId(),
            accountNumber: [$this->getTestAccountNumber()],
        );
    }
}
