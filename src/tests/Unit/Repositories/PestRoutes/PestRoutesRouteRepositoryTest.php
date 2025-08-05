<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes;

use App\DTO\Route\SearchRoutesDTO;
use App\Models\External\RouteModel;
use App\Models\External\ServiceTypeModel;
use App\Repositories\Mappers\PestRoutesRouteToExternalModelMapper;
use App\Repositories\PestRoutes\AbstractPestRoutesRepository;
use App\Repositories\PestRoutes\ParametersFactories\RouteParametersFactory;
use App\Repositories\PestRoutes\PestRoutesRouteRepository;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Resources\Routes\Params\SearchRoutesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Route;
use Aptive\PestRoutesSDK\Resources\Routes\RoutesResource;
use Illuminate\Support\Collection;
use Tests\Data\RouteData;
use Tests\TestCase;
use Tests\Traits\PestRoutesClientMockBuilderAware;
use Tests\Traits\RandomIntTestData;
use Tests\Unit\Repositories\ExtendsAbstractExternalRepository;

final class PestRoutesRouteRepositoryTest extends TestCase
{
    use RandomIntTestData;
    use PestRoutesClientMockBuilderAware;
    use ExtendsAbstractPestRoutesRepository;
    use ExtendsAbstractExternalRepository;

    protected PestRoutesRouteRepository $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new PestRoutesRouteRepository(
            new PestRoutesRouteToExternalModelMapper(),
            new RouteParametersFactory()
        );
    }

    protected function getSubject(): AbstractPestRoutesRepository
    {
        return $this->subject;
    }

    public function test_it_finds_single_route(): void
    {
        $routeId = $this->getTestRouteId();

        /** @var Route $pestRoutesRoute */
        $pestRoutesRoute = RouteData::getTestData(
            1,
            ['routeID' => $routeId]
        )->first();

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(RoutesResource::class)
            ->callSequense('routes', 'find')
            ->methodExpectsArgs('find', [$routeId])
            ->willReturn($pestRoutesRoute)
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClientMock);

        /** @var ServiceTypeModel $result */
        $result = $this->subject
            ->office($this->getTestOfficeId())
            ->find($routeId);

        self::assertEquals($pestRoutesRoute->id, $result->id);
    }

    public function test_it_searches_routes(): void
    {
        $searchDto = new SearchRoutesDTO(
            officeId: $this->getTestOfficeId(),
            latitude: (float) random_int(10, 50),
            longitude: (float) random_int(10, 50),
            maxDistance: random_int(3, 10),
            dateStart: '2022-02-24',
            dateEnd: '2023-04-30'
        );

        $routes = RouteData::getTestData(2);

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(RoutesResource::class)
            ->callSequense('routes', 'includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchRoutesParams $params) use ($searchDto) {
                    $array = $params->toArray();

                    return $array['officeIDs'] === [$searchDto->officeId]
                        && $array['latitude'] === $searchDto->latitude
                        && $array['longitude'] === $searchDto->longitude
                        && $array['maxDistance'] === $searchDto->maxDistance
                        && $array['dateStart'] === $searchDto->dateStart . ' 00:00:00'
                        && $array['dateEnd'] === $searchDto->dateEnd . ' 23:59:59';
                }
            )
            ->willReturn(new PestRoutesCollection($routes->toArray()))
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClientMock);

        $result = $this->subject
            ->office($this->getTestOfficeId())
            ->search($searchDto);

        self::assertCount($routes->count(), $result);
        self::assertEquals(
            $routes->map(fn (Route $route) => $route->id)->toArray(),
            $result->map(fn (RouteModel $route) => $route->id)->toArray(),
        );
    }

    public function test_it_finds_many(): void
    {
        $ids = [
            $this->getTestRouteId(),
            $this->getTestRouteId() + 1,
        ];

        /** @var Collection<int, Route> $routes */
        $routes = RouteData::getTestData(2);

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(RoutesResource::class)
            ->callSequense('routes', 'includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchRoutesParams $params) use ($ids) {
                    $array = $params->toArray();

                    return $array['officeIDs'] === [$this->getTestOfficeId()]
                        && $array['routeIDs'] === $ids;
                }
            )
            ->willReturn(new PestRoutesCollection($routes->all()))
            ->mock();

        $this->subject->setPestRoutesClient($clientMock);

        $result = $this->subject
            ->office($this->getTestOfficeId())
            ->findMany(...$ids);

        $this->assertCount($routes->count(), $result);
    }
}
