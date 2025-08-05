<?php

declare(strict_types=1);

namespace App\Repositories\Mappers;

use App\Interfaces\ExternalModelMapper;
use App\Models\External\SubscriptionModel;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Subscription;

/**
 * @implements ExternalModelMapper<Subscription, SubscriptionModel>
 */
class PestRoutesSubscriptionToExternalModelMapper implements ExternalModelMapper
{
    /**
     * @param Subscription $source
     *
     * @return SubscriptionModel
     */
    public function map(object $source): SubscriptionModel
    {
        return SubscriptionModel::from((array) $source);
    }
}
