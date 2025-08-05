<?php

namespace Tests\Unit\Repositories\PestRoutes\ParametersFactories;

use App\DTO\Spot\SearchSpotsDTO;
use App\Repositories\PestRoutes\ParametersFactories\SpotParametersFactory;
use App\Traits\DateFilterAware;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class SpotParametersFactoryTest extends TestCase
{
    use RandomIntTestData;
    use DateFilterAware;

    protected SpotParametersFactory $factory;

    public function setUp(): void
    {
        parent::setUp();
        $this->factory = new SpotParametersFactory();
    }

    public function test_create_search_returns_valid_search_spots_params(): void
    {
        $routeIds = [1, 2];

        $dto = new SearchSpotsDTO(
            officeId: $this->getTestOfficeId(),
            latitude: (float) random_int(10, 50),
            longitude: (float) random_int(10, 50),
            maxDistance: random_int(3, 10),
            dateStart: '2022-02-24',
            dateEnd: '2023-03-08',
            routeIds: $routeIds
        );

        $searchSpotsParams = $this->factory->createSearch($dto);
        $this->assertEquals(
            [
                'officeIDs' => [$this->getTestOfficeId()],
                'routeIDs' => $routeIds,
                'date' => $this->getDateFilter($dto->getCarbonDateStart(), $dto->getCarbonDateEnd()),
                'apiCanSchedule' => '1',
                'reserved' => '0',
                'includeData' => 0,
                'skipBuild' => 1,
            ],
            $searchSpotsParams->toArray()
        );
    }
}
