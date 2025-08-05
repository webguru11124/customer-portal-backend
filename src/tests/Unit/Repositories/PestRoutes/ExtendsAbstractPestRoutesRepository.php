<?php

namespace Tests\Unit\Repositories\PestRoutes;

use App\DTO\BaseDTO;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Repositories\PestRoutes\AbstractPestRoutesRepository;
use Mockery;

trait ExtendsAbstractPestRoutesRepository
{
    public function test_it_requires_office_set(): void
    {
        $this->expectException(OfficeNotSetException::class);

        $this->getSubject()
            ->search(Mockery::mock(BaseDTO::class));
    }

    abstract protected function getSubject(): AbstractPestRoutesRepository;

    abstract public function expectException(string $exception): void;
}
