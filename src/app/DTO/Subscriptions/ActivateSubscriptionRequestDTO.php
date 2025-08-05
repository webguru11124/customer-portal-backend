<?php

declare(strict_types=1);

namespace App\DTO\Subscriptions;

final class ActivateSubscriptionRequestDTO
{
    public function __construct(
        public readonly int $subscriptionId,
        public readonly int $customerId,
        public readonly int|null $officeId = null,
    ) {
    }
}
