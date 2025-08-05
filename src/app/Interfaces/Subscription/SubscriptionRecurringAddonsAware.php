<?php

declare(strict_types=1);

namespace App\Interfaces\Subscription;

use App\DTO\Subscriptions\SubscriptionAddonRequestDTO;

interface SubscriptionRecurringAddonsAware extends SubscriptionAware
{
    /**
     * @return SubscriptionAddonRequestDTO[]
     */
    public function getSubscriptionRecurringAddons(): array;
}
