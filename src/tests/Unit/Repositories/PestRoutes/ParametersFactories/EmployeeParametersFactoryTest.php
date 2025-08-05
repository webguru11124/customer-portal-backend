<?php

namespace Tests\Unit\Repositories\PestRoutes\ParametersFactories;

use App\DTO\Appointment\SearchAppointmentsDTO;
use App\Repositories\PestRoutes\ParametersFactories\EmployeeParametersFactory;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use TypeError;

class EmployeeParametersFactoryTest extends TestCase
{
    use RandomIntTestData;

    protected EmployeeParametersFactory $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new EmployeeParametersFactory();
    }

    public function test_create_search_throws_error_if_wrong_search_dto_given(): void
    {
        $this->expectException(TypeError::class);

        $this->subject->createSearch(new SearchAppointmentsDTO(
            officeId: $this->getTestOfficeId()
        ));
    }
}
