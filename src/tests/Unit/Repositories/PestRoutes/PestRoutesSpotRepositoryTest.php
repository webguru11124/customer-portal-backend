<?php

namespace Tests\Unit\Repositories\PestRoutes;

use App\DTO\Spot\SearchSpotsDTO;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Models\External\CustomerModel;
use App\Models\External\SpotModel;
use App\Repositories\Mappers\PestRoutesSpotToExternalModelMapper;
use App\Repositories\PestRoutes\AbstractPestRoutesRepository;
use App\Repositories\PestRoutes\ParametersFactories\SpotParametersFactory;
use App\Repositories\PestRoutes\PestRoutesSpotRepository;
use Aptive\PestRoutesSDK\Collection;
use Aptive\PestRoutesSDK\Exceptions\PestRoutesApiException;
use Aptive\PestRoutesSDK\Exceptions\ResourceNotFoundException;
use Aptive\PestRoutesSDK\Resources\Spots\Params\SearchSpotsParams;
use Aptive\PestRoutesSDK\Resources\Spots\Spot;
use Aptive\PestRoutesSDK\Resources\Spots\SpotsResource;
use Illuminate\Support\Collection as LaravelCollection;
use Tests\Data\CustomerData;
use Tests\Data\SpotData;
use Tests\TestCase;
use Tests\Traits\PestRoutesClientMockBuilderAware;
use Tests\Traits\RandomIntTestData;
use Tests\Unit\Repositories\ExtendsAbstractExternalRepository;

class PestRoutesSpotRepositoryTest extends TestCase
{
    use RandomIntTestData;
    use PestRoutesClientMockBuilderAware;
    use ExtendsAbstractPestRoutesRepository;
    use ExtendsAbstractExternalRepository;

    public PestRoutesSpotRepository $pestRoutesSpotRepository;
    public CustomerModel $customer;
    public SearchSpotsDTO $searchSpotDTO;

    public function setUp(): void
    {
        parent::setUp();

        $modelMapper = new PestRoutesSpotToExternalModelMapper();
        $parametersFactory = new SpotParametersFactory();

        $this->pestRoutesSpotRepository = new PestRoutesSpotRepository($modelMapper, $parametersFactory);
        $this->customer = CustomerData::getTestEntityData()->firstOrFail();

        $this->searchSpotDTO = SearchSpotsDTO::from([
            'officeId' => $this->customer->officeId,
            'dateStart' => '2022-08-16',
            'dateEnd' => '2022-08-30',
            'latitude' => $this->customer->latitude,
            'longitude' => $this->customer->longitude,
        ]);
    }

    protected function getSubject(): AbstractPestRoutesRepository
    {
        return $this->pestRoutesSpotRepository;
    }

    public function test_it_searches_spots(): void
    {
        $spotsCollection = SpotData::getTestData(2);
        $pestRoutesClientOutcome = new Collection($spotsCollection->toArray());

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->resource(SpotsResource::class)
            ->callSequense('spots', 'includeData', 'search', 'all')
            ->willReturn($pestRoutesClientOutcome)
            ->mock();

        $this->pestRoutesSpotRepository->setPestRoutesClient($pestRoutesClientMock);

        $searchResult = $this->pestRoutesSpotRepository
            ->office($this->searchSpotDTO->officeId)
            ->search($this->searchSpotDTO);

        self::assertInstanceOf(LaravelCollection::class, $searchResult);
        self::assertCount($spotsCollection->count(), $searchResult);
    }

    public function test_it_finds_single_spot(): void
    {
        $spotData = SpotData::getRawTestData()->first();

        /** @var Spot $spot */
        $spot = SpotData::getTestData(1, $spotData)->first();

        /** @var SpotModel $spotModel */
        $spotModel = SpotData::getTestEntityData(1, $spotData)->first();

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->resource(SpotsResource::class)
            ->callSequense('spots', 'find')
            ->methodExpectsArgs('find', [$spot->id])
            ->willReturn($spot)
            ->mock();

        $this->pestRoutesSpotRepository->setPestRoutesClient($pestRoutesClientMock);

        $result = $this->pestRoutesSpotRepository
            ->office($spot->officeId)
            ->find($spot->id);

        self::assertEquals($spotModel, $result);
    }

    public function test_get_spot_throws_entity_not_found_exception(): void
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(SpotsResource::class)
            ->callSequense('spots', 'find')
            ->willThrow(new ResourceNotFoundException())
            ->mock();

        $this->pestRoutesSpotRepository->setPestRoutesClient($pestRoutesClientMock);

        $this->expectException(EntityNotFoundException::class);

        $this->pestRoutesSpotRepository
            ->office($this->getTestOfficeId())
            ->find($this->getTestAppointmentId());
    }

    public function test_it_finds_many(): void
    {
        $ids = [
            $this->getTestSpotId(),
            $this->getTestSpotId() + 1,
        ];

        /** @var LaravelCollection<int, Spot> $spots */
        $spots = SpotData::getTestData(2);

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(SpotsResource::class)
            ->callSequense('spots', 'includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchSpotsParams $params) use ($ids) {
                    $array = $params->toArray();

                    return $array['officeIDs'] === [$this->getTestOfficeId()]
                        && $array['spotIDs'] === $ids;
                }
            )
            ->willReturn(new Collection($spots->all()))
            ->mock();

        $this->pestRoutesSpotRepository->setPestRoutesClient($clientMock);

        $result = $this->pestRoutesSpotRepository
            ->office($this->getTestOfficeId())
            ->findMany(...$ids);

        $this->assertCount($spots->count(), $result);
    }

    public function test_find_throws_entity_not_found_on_pr_api_error(): void
    {
        $client = $this->getPestRoutesClientMockBuilder()
            ->willThrow(new PestRoutesApiException())
            ->mock();
        $this->pestRoutesSpotRepository->setPestRoutesClient($client);

        $this->expectException(EntityNotFoundException::class);

        $this->pestRoutesSpotRepository
            ->office($this->getTestOfficeId())
            ->find($this->getTestSpotId());
    }
}
