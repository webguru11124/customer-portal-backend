<?php

namespace Tests\Traits;

use App\Interfaces\Repository\AppointmentRepository;
use App\Models\External\AppointmentModel;
use Mockery\MockInterface;
use Throwable;

trait MockFindAppointment
{
    protected function mockFindAppointment(int $findArgument, AppointmentModel|Throwable $expectedResult): void
    {
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

        $expectation = $this->appointmentRepositoryMock
            ->shouldReceive('find')
            ->withArgs([$findArgument])
            ->once();

        if ($expectedResult instanceof Throwable) {
            $expectation->andThrow($expectedResult);
        } else {
            $expectation->andReturn($expectedResult);
        }
    }

    abstract protected function getAppointmentRepositoryMock(): MockInterface|AppointmentRepository;
}
