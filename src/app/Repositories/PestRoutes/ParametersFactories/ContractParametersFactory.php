<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\ParametersFactories;

use App\DTO\Contract\SearchContractsDTO;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\Contracts\Params\SearchContractsParams;

class ContractParametersFactory extends AbstractPestRoutesHttpParametersFactory
{
    /**
     * @param SearchContractsDTO $searchDto
     *
     * @return SearchContractsParams
     */
    public function createSearch(mixed $searchDto): AbstractHttpParams
    {
        $this->validateInput(SearchContractsDTO::class, $searchDto);

        return new SearchContractsParams(
            officeIds: [$searchDto->officeId],
            customerIds: $searchDto->accountNumbers,
            includeDocumentLink: true,
        );
    }
}
