<?php

namespace Tests\Unit\Repositories\PestRoutes\ParametersFactories;

use App\DTO\Appointment\SearchAppointmentsDTO;
use App\Repositories\PestRoutes\ParametersFactories\RouteParametersFactory;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use TypeError;

class RouteParametersFactoryTest extends TestCase
{
    use RandomIntTestData;

    protected RouteParametersFactory $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new RouteParametersFactory();
    }

    public function test_create_search_throws_error_if_wrong_search_dto_given(): void
    {
        $this->expectException(TypeError::class);

        $this->subject->createSearch(new SearchAppointmentsDTO(
            officeId: $this->getTestOfficeId()
        ));
    }
}
