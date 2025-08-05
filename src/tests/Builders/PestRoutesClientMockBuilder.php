<?php

namespace Tests\Builders;

use Aptive\PestRoutesSDK\Client;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Closure;
use Mockery;
use Mockery\MockInterface;
use Throwable;

class PestRoutesClientMockBuilder
{
    private int $times = 1;
    private int $officeId = 1;
    private string $resource;
    private array $methods = [];
    private mixed $outcome;
    private array $expectedArgs = [];

    public function times(int $times): self
    {
        $this->times = $times;

        return $this;
    }

    public function resource(string $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    public function methodExpectsArgs(string $method, array|Closure $args): self
    {
        $this->expectedArgs[$method] = $args;

        return $this;
    }

    public function callSequense(string ...$methods): self
    {
        $this->methods = $methods;

        return $this;
    }

    public function office(int $officeId): self
    {
        $this->officeId = $officeId;

        return $this;
    }

    public function willReturn(mixed $outcome): self
    {
        $this->outcome = $outcome;

        return $this;
    }

    public function willThrow(Throwable $throwable): self
    {
        $this->outcome = $throwable;

        return $this;
    }

    public function mock(): MockInterface|Client
    {
        if ($this->outcome instanceof Throwable) {
            return $this->mockPestRoutesClientThatThrowsError($this->outcome);
        }

        $callSequence = array_reverse($this->methods);
        $officeResourceMethod = array_pop($callSequence);
        $methodOutcome = $this->outcome;

        foreach ($callSequence as $method) {
            $expectedResourceMock = Mockery::mock($this->resource);

            $expectation = $expectedResourceMock->shouldReceive($method);

            if (!empty($this->expectedArgs[$method])) {
                $expectation->withArgs($this->expectedArgs[$method]);
            }
            $expectation
                ->andReturn($methodOutcome)
                ->times($this->times);

            $methodOutcome = $expectedResourceMock;
        }

        if (empty($expectedResourceMock)) {
            $expectedResourceMock = $this->outcome;
        }

        $officeResourceMock = Mockery::mock(OfficesResource::class);
        $officeResourceMock
            ->shouldReceive($officeResourceMethod)
            ->andReturn($expectedResourceMock)
            ->times($this->times);

        $pestRoutesClientMock = Mockery::mock(Client::class);
        $pestRoutesClientMock
            ->shouldReceive('office')
            ->withArgs([$this->officeId])
            ->andReturn($officeResourceMock)
            ->times($this->times);

        return $pestRoutesClientMock;
    }

    private function mockPestRoutesClientThatThrowsError(Throwable $exception): MockInterface|Client
    {
        $pestRoutesClientMock = Mockery::mock(Client::class);
        $pestRoutesClientMock->shouldReceive('office')->andThrow($exception);

        return $pestRoutesClientMock;
    }
}
