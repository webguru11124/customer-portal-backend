<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\ParametersFactories;

use App\DTO\Subscriptions\SearchSubscriptionsDTO;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Params\SearchSubscriptionsParams;

class SubscriptionParametersFactory extends AbstractPestRoutesHttpParametersFactory
{
    /**
     * @param SearchSubscriptionsDTO $searchDto
     *
     * @return SearchSubscriptionsParams
     */
    public function createSearch(mixed $searchDto): AbstractHttpParams
    {
        $this->validateInput(SearchSubscriptionsDTO::class, $searchDto);

        return new SearchSubscriptionsParams(
            ids: $searchDto->ids,
            officeIds: $searchDto->officeIds,
            active: $searchDto->isActive === null ? null : (int) $searchDto->isActive,
            customerIds: $searchDto->customerIds
        );
    }
}
