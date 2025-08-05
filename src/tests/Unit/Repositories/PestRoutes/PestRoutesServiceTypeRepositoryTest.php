<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes;

use App\DTO\ServiceType\SearchServiceTypesDTO;
use App\Models\External\ServiceTypeModel;
use App\Repositories\Mappers\PestRoutesServiceTypeToExternalModelMapper;
use App\Repositories\PestRoutes\AbstractPestRoutesRepository;
use App\Repositories\PestRoutes\ParametersFactories\ServiceTypeParametersFactory;
use App\Repositories\PestRoutes\PestRoutesServiceTypeRepository;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Exceptions\ResourceNotFoundException;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\Params\SearchServiceTypesParams;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\ServiceType;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\ServiceTypesResource;
use Illuminate\Support\Collection;
use Tests\Data\ServiceTypeData;
use Tests\TestCase;
use Tests\Traits\PestRoutesClientMockBuilderAware;
use Tests\Traits\RandomIntTestData;
use Tests\Unit\Repositories\ExtendsAbstractExternalRepository;

final class PestRoutesServiceTypeRepositoryTest extends TestCase
{
    use RandomIntTestData;
    use PestRoutesClientMockBuilderAware;
    use ExtendsAbstractPestRoutesRepository;
    use ExtendsAbstractExternalRepository;

    protected PestRoutesServiceTypeRepository $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new PestRoutesServiceTypeRepository(
            new PestRoutesServiceTypeToExternalModelMapper(),
            new ServiceTypeParametersFactory()
        );
    }

    protected function getSubject(): AbstractPestRoutesRepository
    {
        return $this->subject;
    }

    public function pestRoutesClientExceptionProvider(): array
    {
        return [
            'Internal Server error' => [new InternalServerErrorHttpException()],
            'Resource Not Found' => [new ResourceNotFoundException()],
        ];
    }

    public function test_it_finds_single_service_type(): void
    {
        /** @var ServiceType $pestRoutesServiceType */
        $pestRoutesServiceType = ServiceTypeData::getTestData(
            1,
            ['typeID' => $this->getTestServiceTypeId()]
        )->first();

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(ServiceTypesResource::class)
            ->callSequense('serviceTypes', 'find')
            ->methodExpectsArgs('find', [$this->getTestServiceTypeId()])
            ->willReturn($pestRoutesServiceType)
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClientMock);

        /** @var ServiceTypeModel $result */
        $result = $this->subject
            ->office($this->getTestOfficeId())
            ->find($this->getTestServiceTypeId());

        self::assertEquals($pestRoutesServiceType->id, $result->id);
    }

    public function test_it_searches_service_types(): void
    {
        $serviceTypesIds = [
            ServiceTypeData::PRO,
            ServiceTypeData::QUARTERLY_SERVICE,
            ServiceTypeData::PRO_PLUS,
            ServiceTypeData::MOSQUITO,
            ServiceTypeData::PREMIUM,
            ServiceTypeData::RESERVICE,
        ];

        $typesAmount = random_int(1, count($serviceTypesIds));

        $searchIds = array_map(
            fn (int $idx) => $serviceTypesIds[$idx],
            (array) array_rand($serviceTypesIds, $typesAmount)
        );
        $serviceTypes = ServiceTypeData::getTestDataOfTypes(...$searchIds);

        $searchDto = new SearchServiceTypesDTO(
            ids: $searchIds,
            officeIds: [$this->getTestOfficeId()]
        );

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(ServiceTypesResource::class)
            ->callSequense('serviceTypes', 'includeData', 'search', 'all')
            ->willReturn(new PestRoutesCollection($serviceTypes->toArray()))
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClientMock);

        $result = $this->subject
            ->office($this->getTestOfficeId())
            ->search($searchDto);

        self::assertCount($typesAmount, $result);
    }

    public function test_it_finds_many(): void
    {
        $ids = [
            $this->getTestServiceTypeId(),
            $this->getTestServiceTypeId() + 1,
        ];

        /** @var Collection<int, ServiceType> $serviceTypes */
        $serviceTypes = ServiceTypeData::getTestData(2);

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(ServiceTypesResource::class)
            ->callSequense('serviceTypes', 'includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchServiceTypesParams $params) use ($ids) {
                    $array = $params->toArray();

                    return $array['officeIDs'] === array_merge([$this->getTestOfficeId()], [-1])
                        && $array['typeIDs'] === $ids;
                }
            )
            ->willReturn(new PestRoutesCollection($serviceTypes->all()))
            ->mock();

        $this->subject->setPestRoutesClient($clientMock);

        $result = $this->subject
            ->office($this->getTestOfficeId())
            ->findMany(...$ids);

        $this->assertCount($serviceTypes->count(), $result);
    }
}
