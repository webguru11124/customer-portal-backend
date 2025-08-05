<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Facades\Config;

final class SubscriptionAddonsConfigHelper
{
    public static function getAddonDefaultAmount(): float
    {
        return (float) Config::get('aptive.subscription.addons_default_values.amount');
    }

    public static function getAddonDefaultTaxable(): bool
    {
        return (bool) Config::get('aptive.subscription.addons_default_values.taxable');
    }

    public static function getAddonDefaultName(): string
    {
        return (string) Config::get('aptive.subscription.addons_default_values.name');
    }

    public static function getAddonDefaultQuantity(): int
    {
        return (int) Config::get('aptive.subscription.addons_default_values.quantity');
    }

    public static function getAddonDefaultServiceId(): int
    {
        return (int) Config::get('aptive.subscription.addons_default_values.service_id');
    }

    public static function getAddonDefaultCreditTo(): int
    {
        return (int) Config::get('aptive.subscription.addons_default_values.credit_to');
    }

    /**
     * @return array<int, string>
     */
    public static function getDisallowedAddonsPests(): array
    {
        return (array) Config::get('aptive.subscription.addons_exceptions.disallowed_pests');
    }
}
