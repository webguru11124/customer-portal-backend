<?php

namespace Tests\Unit\Repositories\PestRoutes\ParametersFactories;

use App\DTO\Appointment\SearchAppointmentsDTO;
use App\DTO\ServiceType\SearchServiceTypesDTO;
use App\Repositories\PestRoutes\ParametersFactories\ServiceTypeParametersFactory;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\Params\SearchServiceTypesParams;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use TypeError;

class ServiceTypeParametersFactoryTest extends TestCase
{
    use RandomIntTestData;

    protected ServiceTypeParametersFactory $subject;

    private const MUTUAL_OFFICE_ID = -1;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new ServiceTypeParametersFactory();
    }

    public function test_create_search_throws_error_if_wrong_search_dto_given(): void
    {
        $this->expectException(TypeError::class);

        $this->subject->createSearch(new SearchAppointmentsDTO(
            officeId: $this->getTestOfficeId()
        ));
    }

    public function test_it_adds_mutual_office_id(): void
    {
        $officeId = $this->getTestOfficeId();

        /** @var SearchServiceTypesParams $searchParams */
        $searchParams = $this->subject->createSearch(new SearchServiceTypesDTO(
            ids: [$this->getTestServiceTypeId()],
            officeIds: [$this->getTestOfficeId()]
        ));

        $resultArray = $searchParams->toArray();
        $expectedOfficeIDs = [$officeId, self::MUTUAL_OFFICE_ID];

        self::assertSame($resultArray['officeIDs'], $expectedOfficeIDs);
    }
}
