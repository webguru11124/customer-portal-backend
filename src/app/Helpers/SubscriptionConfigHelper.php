<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Facades\Config;

final class SubscriptionConfigHelper
{
    public static function getFrozenSubscriptionFollowupDelay(): int
    {
        return (int) Config::get('aptive.subscription.frozen.followupDelay');
    }

    public static function isFrozenSubscriptionActive(): bool
    {
        return (bool) Config::get('aptive.subscription.frozen.isActive');
    }

    public static function getFrozenSubscriptionFlag(): int|null
    {
        $subscriptionFlag = Config::get('aptive.subscription.frozen.flag');

        return $subscriptionFlag ? (int) $subscriptionFlag : null;
    }
}
