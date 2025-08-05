<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes;

use App\DTO\Office\SearchOfficesDTO;
use App\Helpers\ConfigHelper;
use App\Models\External\OfficeModel;
use App\Repositories\Mappers\PestRoutesOfficeToExternalModelMapper;
use App\Repositories\PestRoutes\AbstractPestRoutesRepository;
use App\Repositories\PestRoutes\ParametersFactories\OfficeParametersFactory;
use App\Repositories\PestRoutes\PestRoutesOfficeRepository;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Resources\Offices\Office;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Offices\Params\SearchOfficesParams;
use Illuminate\Support\Collection;
use Tests\Data\OfficeData;
use Tests\TestCase;
use Tests\Traits\PestRoutesClientMockBuilderAware;
use Tests\Traits\RandomIntTestData;
use Tests\Unit\Repositories\ExtendsAbstractExternalRepository;

final class PestRoutesOfficeRepositoryTest extends TestCase
{
    use RandomIntTestData;
    use PestRoutesClientMockBuilderAware;
    use ExtendsAbstractExternalRepository;

    protected PestRoutesOfficeRepository $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new PestRoutesOfficeRepository(
            new PestRoutesOfficeToExternalModelMapper(),
            new OfficeParametersFactory()
        );
    }

    protected function getSubject(): AbstractPestRoutesRepository
    {
        return $this->subject;
    }

    public function test_it_finds_single_office(): void
    {
        /** @var Office $pestRoutesOffice */
        $pestRoutesOffice = OfficeData::getTestData(
            1,
            ['officeID' => $this->getTestOfficeId()]
        )->first();

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(ConfigHelper::getGlobalOfficeId())
            ->resource(OfficesResource::class)
            ->callSequense('find')
            ->methodExpectsArgs('find', [$this->getTestOfficeId()])
            ->willReturn($pestRoutesOffice)
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClientMock);

        /** @var officeModel $result */
        $result = $this->subject
            ->office(ConfigHelper::getGlobalOfficeId())
            ->find($this->getTestOfficeId());

        $this->assertEquals($pestRoutesOffice->id, $result->id);
        $this->assertEquals($pestRoutesOffice->officeName, $result->officeName);
    }

    public function test_it_searches_offices(): void
    {
        $searchDto = new SearchOfficesDTO(ids: [$this->getTestOfficeId(), $this->getTestOfficeId() + 1]);

        /** @var Collection $pestRoutesOffice */
        $pestRoutesOffices = OfficeData::getTestData(
            2,
            ['officeID' => $this->getTestOfficeId()],
            ['officeID' => $this->getTestOfficeId() + 1],
        );

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(ConfigHelper::getGlobalOfficeId())
            ->resource(OfficesResource::class)
            ->callSequense('includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchOfficesParams $params) {
                    $paramsArray = $params->toArray();

                    return $paramsArray['includeData'] === 0;
                }
            )
            ->willReturn(new PestRoutesCollection($pestRoutesOffices->toArray()))
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClientMock);

        $result = $this->subject
            ->office($this->getTestOfficeId())
            ->search($searchDto);

        $this->assertCount($pestRoutesOffices->count(), $result);
        $this->assertEquals($pestRoutesOffices[0]->id, $result[0]->id);
        $this->assertEquals($pestRoutesOffices[1]->officeName, $result[1]->officeName);
    }

    public function test_it_searches_office_ids(): void
    {
        /** @var Collection $pestRoutesOffice */
        $pestRoutesOffices = OfficeData::getTestData(
            2,
            ['officeID' => $this->getTestOfficeId()],
            ['officeID' => $this->getTestOfficeId() + 1],
        );

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(ConfigHelper::getGlobalOfficeId())
            ->resource(OfficesResource::class)
            ->callSequense('includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchOfficesParams $params) {
                    $paramsArray = $params->toArray();

                    return $paramsArray['includeData'] === 0;
                }
            )
            ->willReturn(new PestRoutesCollection($pestRoutesOffices->toArray()))
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClientMock);

        $result = $this->subject->getAllOfficeIds();

        $this->assertEquals([$this->getTestOfficeId(), $this->getTestOfficeId() + 1], $result);
    }

    public function test_it_finds_many(): void
    {
        $ids = [
            $this->getTestOfficeId(),
            $this->getTestOfficeId() + 1,
        ];

        /** @var Collection<int, Office> $offices */
        $offices = OfficeData::getTestData(2);

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office(0)
            ->resource(OfficesResource::class)
            ->callSequense('includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchOfficesParams $params) use ($ids) {
                    $array = $params->toArray();

                    return $array['includeData'] === 0;
                }
            )
            ->willReturn(new PestRoutesCollection($offices->all()))
            ->mock();

        $this->subject->setPestRoutesClient($clientMock);

        $result = $this->subject
            ->office(0)
            ->findMany(...$ids);

        $this->assertCount($offices->count(), $result);
    }
}
