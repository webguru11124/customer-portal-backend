<?php

namespace Tests\Data;

use App\Models\External\RouteModel;
use App\Repositories\Mappers\PestRoutesRouteToExternalModelMapper;
use Aptive\PestRoutesSDK\Resources\Routes\Route;

/**
 * @extends AbstractTestPestRoutesData<Route, RouteModel>
 */
class RouteData extends AbstractTestPestRoutesData
{
    public const DATE_FORMAT = 'Y-m-d';

    protected static function getSignature(): array
    {
        return [
            'routeID' => random_int(10000, 99999),
            'title' => 'Regular Routes',
            'templateID' => '57',
            'dateAdded' => '2023-03-17 23:02:12',
            'addedBy' => '76354',
            'officeID' => random_int(2, 100),
            'groupTitle' => 'Regular Routes',
            'groupID' => 0,
            'date' => '2023-04-17',
            'dayNotes' => '',
            'dayAlert' => '',
            'dayID' => '295556',
            'additionalTechs' => null,
            'assignedTech' => '482547',
            'apiCanSchedule' => '1',
            'scheduleTeams' => [],
            'scheduleTypes' => [
                '0',
                '1',
                '2',
                '3',
                '4',
            ],
            'averageLatitude' => null,
            'averageLongitude' => null,
            'averageDistance' => null,
            'dateUpdated' => '2023-03-17 23:02:12',
            'distanceScore' => '0',
            'lockedRoute' => '0',
        ];
    }

    protected static function getRequiredEntityClass(): string
    {
        return Route::class;
    }

    protected static function getMapperClass(): string
    {
        return PestRoutesRouteToExternalModelMapper::class;
    }
}
