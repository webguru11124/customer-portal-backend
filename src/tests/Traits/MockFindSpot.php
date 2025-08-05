<?php

namespace Tests\Traits;

use App\Interfaces\Repository\SpotRepository;
use App\Models\External\SpotModel;
use Mockery\MockInterface;
use Throwable;

trait MockFindSpot
{
    protected function mockFindSpot(int $officeId, int $findArgument, SpotModel|Throwable $expectedResult): void
    {
        $this->getSpotRepositoryMock()
            ->shouldReceive('office')
            ->withArgs([$officeId])
            ->andReturn($this->getSpotRepositoryMock())
            ->once();

        $expectation = $this->getSpotRepositoryMock()
            ->shouldReceive('find')
            ->withArgs([$findArgument])
            ->once();

        if ($expectedResult instanceof Throwable) {
            $expectation->andThrow($expectedResult);
        } else {
            $expectation->andReturn($expectedResult);
        }
    }

    abstract protected function getSpotRepositoryMock(): MockInterface|SpotRepository;
}
