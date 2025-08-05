<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\ParametersFactories;

use App\DTO\Office\SearchOfficesDTO;
use Aptive\PestRoutesSDK\Filters\NumberFilter;
use Aptive\PestRoutesSDK\Resources\Offices\Params\SearchOfficesParams;

class OfficeParametersFactory extends AbstractPestRoutesHttpParametersFactory
{
    /**
     * @param SearchOfficesDTO $searchDto
     *
     * @return SearchOfficesParams
     */
    public function createSearch(mixed $searchDto): SearchOfficesParams
    {
        $this->validateInput(SearchOfficesDTO::class, $searchDto);

        return new SearchOfficesParams(
            officeId: is_array($searchDto->ids) ? NumberFilter::in($searchDto->ids) : null
        );
    }
}
