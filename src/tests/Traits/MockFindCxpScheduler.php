<?php

namespace Tests\Traits;

use App\Exceptions\Entity\EntityNotFoundException;
use App\Interfaces\Repository\EmployeeRepository;
use Mockery\MockInterface;
use Tests\Data\EmployeeData;

trait MockFindCxpScheduler
{
    private function mockFindCxpScheduler(
        MockInterface|EmployeeRepository $employeeRepositoryMock,
        int $officeId,
        int|null $employeeId
    ): MockInterface|EmployeeRepository {
        $employeeRepositoryMock
            ->shouldReceive('office')
            ->withArgs([$officeId])
            ->andReturn($employeeRepositoryMock)
            ->once();

        $employeeRepoExpectation = $employeeRepositoryMock
            ->shouldReceive('findCxpScheduler')
            ->withNoArgs()
            ->once();

        if ($employeeId === null) {
            $employeeRepoExpectation->andThrow(new EntityNotFoundException());
        } else {
            $employeeModel = EmployeeData::getTestEntityData(
                1,
                ['employeeID' => $employeeId]
            )->first();

            $employeeRepoExpectation->andReturn($employeeModel);
        }

        return $employeeRepositoryMock;
    }
}
