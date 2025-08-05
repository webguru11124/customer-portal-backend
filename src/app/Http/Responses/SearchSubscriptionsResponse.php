<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Enums\Resources;
use App\Models\External\SubscriptionModel;
use App\Traits\ObjectToResource;
use App\Traits\ValidateObjectClass;

class SearchSubscriptionsResponse extends AbstractSearchResponse
{
    use ValidateObjectClass;
    use ObjectToResource;

    protected function getExpectedEntityClass(): string
    {
        return SubscriptionModel::class;
    }

    protected function getExpectedResourceType(): Resources
    {
        return Resources::SUBSCRIPTION;
    }

    protected function additionalAttributes(): array
    {
        return [
            'serviceType' => fn (SubscriptionModel $subscription) => $subscription->serviceType->description,
        ];
    }
}
