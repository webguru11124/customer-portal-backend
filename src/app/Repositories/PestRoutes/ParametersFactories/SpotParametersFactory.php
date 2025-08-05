<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\ParametersFactories;

use App\DTO\Spot\SearchSpotsDTO;
use App\Traits\DateFilterAware;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\Spots\Params\SearchSpotsParams;

class SpotParametersFactory extends AbstractPestRoutesHttpParametersFactory
{
    use DateFilterAware;

    /**
     * @param SearchSpotsDTO $searchDto
     *
     * @return SearchSpotsParams
     */
    public function createSearch(mixed $searchDto): AbstractHttpParams
    {
        $this->validateInput(SearchSpotsDTO::class, $searchDto);

        return new SearchSpotsParams(
            officeIds: [$searchDto->officeId],
            date: $this->getDateFilter(
                $searchDto->getCarbonDateStart(),
                $searchDto->getCarbonDateEnd()
            ),
            apiCanSchedule: true,
            routeIds: $searchDto->routeIds,
            isReserved: $searchDto->isReserved,
            ids: $searchDto->ids,
            latitude: $searchDto->latitude,
            longitude: $searchDto->longitude,
            maxDistance: $searchDto->maxDistance,
            onlyOpen: $searchDto->onlyOpen
        );
    }
}
