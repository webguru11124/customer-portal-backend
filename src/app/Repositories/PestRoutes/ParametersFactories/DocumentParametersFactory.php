<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\ParametersFactories;

use App\DTO\Document\SearchDocumentsDTO;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\Documents\Params\SearchDocumentsParams;

class DocumentParametersFactory extends AbstractPestRoutesHttpParametersFactory
{
    /**
     * @param SearchDocumentsDTO $searchDto
     *
     * @return SearchDocumentsParams
     */
    public function createSearch(mixed $searchDto): AbstractHttpParams
    {
        $this->validateInput(SearchDocumentsDTO::class, $searchDto);

        return new SearchDocumentsParams(
            ids: $searchDto->ids,
            officeId: $searchDto->officeId,
            customerId: $searchDto->accountNumber,
            appointmentIds: $searchDto->appointmentIds,
        );
    }
}
