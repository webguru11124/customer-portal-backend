<?php

declare(strict_types=1);

namespace App\DTO\Subscriptions;

use App\DTO\BaseDTO;

class DeactivateSubscriptionResponseDTO extends BaseDTO
{
    public function __construct(
        public readonly int $subscriptionId
    ) {
    }
}
