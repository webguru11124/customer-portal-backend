<?php

namespace Tests\Unit\Repositories\PestRoutes\ParametersFactories;

use App\DTO\BaseDTO;
use App\Repositories\PestRoutes\ParametersFactories\OfficeParametersFactory;
use Tests\TestCase;
use TypeError;

class OfficeParametersFactoryTest extends TestCase
{
    protected OfficeParametersFactory $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new OfficeParametersFactory();
    }

    public function test_create_search_throws_error_if_wrong_search_dto_given(): void
    {
        $this->expectException(TypeError::class);

        $this->subject->createSearch(new BaseDTO());
    }
}
