<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\ParametersFactories;

use App\DTO\Route\SearchRoutesDTO;
use Aptive\PestRoutesSDK\Converters\DateTimeConverter;
use Aptive\PestRoutesSDK\Resources\Routes\Params\SearchRoutesParams;

class RouteParametersFactory extends AbstractPestRoutesHttpParametersFactory
{
    /**
     * @param SearchRoutesDTO $searchDto
     */
    public function createSearch(mixed $searchDto): SearchRoutesParams
    {
        $this->validateInput(SearchRoutesDTO::class, $searchDto);

        return new SearchRoutesParams(
            routeIds: $searchDto->ids,
            officeIds: [$searchDto->officeId],
            dateStart: $searchDto->getCarbonDateStart(DateTimeConverter::PEST_ROUTES_TIMEZONE),
            dateEnd: $searchDto->getCarbonDateEnd(DateTimeConverter::PEST_ROUTES_TIMEZONE),
            apiCanSchedule: true,
            latitude: $searchDto->latitude,
            longitude: $searchDto->longitude,
            maxDistance: $searchDto->maxDistance
        );
    }
}
