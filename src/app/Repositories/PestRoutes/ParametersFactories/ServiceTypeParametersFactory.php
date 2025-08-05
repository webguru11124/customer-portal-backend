<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\ParametersFactories;

use App\DTO\ServiceType\SearchServiceTypesDTO;
use App\Helpers\ConfigHelper;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\Params\SearchServiceTypesParams;

class ServiceTypeParametersFactory extends AbstractPestRoutesHttpParametersFactory
{
    /**
     * @param SearchServiceTypesDTO $searchDto
     *
     * @return SearchServiceTypesParams
     */
    public function createSearch(mixed $searchDto): AbstractHttpParams
    {
        $this->validateInput(SearchServiceTypesDTO::class, $searchDto);

        return new SearchServiceTypesParams(
            ids: $searchDto->ids,
            officeIds: array_merge($searchDto->officeIds, [ConfigHelper::getServiceTypeMutualOfficeID()])
        );
    }
}
