<?php

namespace Tests\Unit\Repositories\PestRoutes\ParametersFactories;

use App\DTO\Appointment\SearchAppointmentsDTO;
use App\DTO\ServiceType\SearchServiceTypesDTO;
use App\Repositories\PestRoutes\ParametersFactories\AppointmentParametersFactory;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use TypeError;

class AppointmentParametersFactoryTest extends TestCase
{
    use RandomIntTestData;

    protected AppointmentParametersFactory $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new AppointmentParametersFactory();
    }

    public function test_create_search_throws_error_if_wrong_search_dto_given(): void
    {
        $this->expectException(TypeError::class);

        $this->subject->createSearch(new SearchServiceTypesDTO());
    }

    public function test_create_search_sets_both_officeID_and_officeIDs(): void
    {
        $searchDto = new SearchAppointmentsDTO($this->getTestOfficeId());

        $searchParams = $this->subject->createSearch($searchDto);

        $this->assertSame([
            'officeID' => $this->getTestOfficeId(),
            'officeIDs' => [$this->getTestOfficeId()],
            'includeData' => 0,
        ], $searchParams->toArray());
    }
}
