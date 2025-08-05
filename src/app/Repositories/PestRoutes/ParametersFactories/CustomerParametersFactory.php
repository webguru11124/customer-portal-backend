<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\ParametersFactories;

use App\DTO\Customer\SearchCustomersDTO;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\Customers\Params\SearchCustomersParams;

class CustomerParametersFactory extends AbstractPestRoutesHttpParametersFactory
{
    /**
     * @param SearchCustomersDTO $searchDto
     *
     * @return SearchCustomersParams
     */
    public function createSearch(mixed $searchDto): AbstractHttpParams
    {
        $this->validateInput(SearchCustomersDTO::class, $searchDto);

        return new SearchCustomersParams(
            ids: $searchDto->accountNumbers,
            officeIds: $searchDto->officeIds,
            isActive: $searchDto->isActive,
            email: $searchDto->email,
            includeCancellationReason: $searchDto->includeCancellationReason,
            includeSubscriptions: false,
            includeCustomerFlag: $searchDto->includeCustomerFlag,
            includeAdditionalContacts: $searchDto->includeAdditionalContacts,
            includePortalLogin: $searchDto->includePortalLogin
        );
    }
}
