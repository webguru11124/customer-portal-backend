<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\ParametersFactories;

use App\DTO\Form\SearchFormsDTO;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\Forms\Params\SearchFormsParams;

class FormParametersFactory extends AbstractPestRoutesHttpParametersFactory
{
    /**
     * @param SearchFormsDTO $searchDto
     *
     * @return SearchFormsParams
     */
    public function createSearch(mixed $searchDto): AbstractHttpParams
    {
        $this->validateInput(SearchFormsDTO::class, $searchDto);

        return new SearchFormsParams(
            formIds: $searchDto->formIds ?? [],
            officeIds: [$searchDto->officeId],
            customerId: $searchDto->accountNumber,
            includeDocumentLink: true,
        );
    }
}
