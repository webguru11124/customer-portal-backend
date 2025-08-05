<?php

declare(strict_types=1);

namespace App\Interfaces\Repository;

use App\DTO\Subscriptions\SubscriptionAddonRequestDTO;
use App\Models\External\SubscriptionAddonModel;

/**
 * @extends ExternalRepository<SubscriptionAddonModel>
 */
interface SubscriptionAddonRepository extends ExternalRepository
{
    public function createInitialAddon(int $subscriptionId, SubscriptionAddonRequestDTO $initialAddon): int;
}
