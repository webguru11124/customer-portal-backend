<?php

declare(strict_types=1);

namespace App\Repositories\Mappers;

use App\Interfaces\ExternalModelMapper;
use App\Models\External\AbstractExternalModel;
use App\Models\External\SubscriptionAddonModel;
use Aptive\PestRoutesSDK\Resources\Subscriptions\SubscriptionAddon;

/**
 * @implements ExternalModelMapper<SubscriptionAddon, SubscriptionAddonModel>
 */
class PestRoutesSubscriptionAddonToExternalModelMapper implements ExternalModelMapper
{
    /**
     * @param SubscriptionAddon $source
     *
     * @return SubscriptionAddonModel
     */
    public function map(object $source): AbstractExternalModel
    {
        return SubscriptionAddonModel::from((array) $source);
    }
}
