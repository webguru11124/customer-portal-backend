<?php

declare(strict_types=1);

namespace Tests\Data;

final class CreateSubscriptionRequestData
{
    public static function getRequest(): array
    {
        return [
            'plan_id' => 1800,
            'agreement_length' => 12,
            'plan_price_per_treatment' => 169,
            'plan_price_initial' => 169,
            'initial_addons' => [],
            'recurring_addons' => [],
        ];
    }
}
