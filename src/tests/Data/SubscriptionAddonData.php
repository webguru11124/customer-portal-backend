<?php

declare(strict_types=1);

namespace Tests\Data;

use App\Models\External\SubscriptionAddonModel;
use App\Repositories\Mappers\PestRoutesSubscriptionAddonToExternalModelMapper;
use Aptive\PestRoutesSDK\Resources\Subscriptions\SubscriptionAddon;

/**
 * @extends AbstractTestPestRoutesData<SubscriptionAddon, SubscriptionAddonModel>
 */
final class SubscriptionAddonData extends AbstractTestPestRoutesData
{
    protected static function getRequiredEntityClass(): string
    {
        return SubscriptionAddon::class;
    }

    protected static function getSignature(): array
    {
        return [
            'addOnID' => random_int(100, PHP_INT_MAX),
            'productID' => random_int(100, PHP_INT_MAX),
            'subscriptionID' => random_int(100, PHP_INT_MAX),
            'ticketID' => random_int(100, PHP_INT_MAX),
            'serviceID' => random_int(100, PHP_INT_MAX),
            'code' => 'Test code',
            'category' => 'Test category',
            'amount' => random_int(100, PHP_INT_MAX),
            'description' => 'Test Description',
            'taxable' => '1',
            'creditTo' => random_int(0, 100),
            'quantity' => 1,
        ];
    }

    protected static function getMapperClass(): string
    {
        return PestRoutesSubscriptionAddonToExternalModelMapper::class;
    }
}
