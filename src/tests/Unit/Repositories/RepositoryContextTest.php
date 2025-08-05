<?php

namespace Tests\Unit\Repositories;

use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Repositories\RepositoryContext;
use PHPUnit\Framework\TestCase;

class RepositoryContextTest extends TestCase
{
    protected RepositoryContext $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new RepositoryContext();
    }

    public function test_it_throws_an_exception_when_trying_to_get_not_set_office_id(): void
    {
        $this->expectException(OfficeNotSetException::class);

        $this->subject->getOfficeId();
    }
}
