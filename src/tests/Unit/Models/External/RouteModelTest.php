<?php

declare(strict_types=1);

namespace Tests\Unit\Models\External;

use App\Interfaces\Repository\RouteRepository;
use App\Models\External\RouteModel;
use Tests\Data\RouteData;
use Tests\TestCase;

class RouteModelTest extends TestCase
{
    public function test_it_returns_proper_repository_class(): void
    {
        self::assertEquals(RouteRepository::class, RouteModel::getRepositoryClass());
    }

    /**
     * @dataProvider initialRouteDataProvider
     *
     * @param array<string, string> $routeData
     * @param bool $expectedResult
     */
    public function test_it_determines_if_route_is_initial(array $routeData, bool $expectedResult): void
    {
        /** @var RouteModel $route */
        $route = RouteData::getTestEntityData(1, $routeData)->first();

        self::assertEquals($expectedResult, $route->isInitial());
    }

    /**
     * @return iterable<int, mixed>
     */
    public function initialRouteDataProvider(): iterable
    {
        yield [
            [
                'groupTitle' => 'Regular Routes',
                'groupID' => 0,
                'title' => 'Regular Route',
            ],
            false,
        ];
        yield [
            [
                'groupTitle' => 'Some other Routes',
                'groupID' => 0,
                'title' => 'Some other Route',
            ],
            false,
        ];
        yield [
            [
                'groupTitle' => 'Initial Routes',
                'groupID' => 0,
                'title' => 'Some other Route',
            ],
            true,
        ];
        yield [
            [
                'groupTitle' => 'Some other Routes',
                'groupID' => 0,
                'title' => 'Route INITIAL',
            ],
            true,
        ];
    }
}
