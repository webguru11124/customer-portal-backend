<?php

declare(strict_types=1);

namespace App\DTO\Subscriptions;

use App\DTO\BaseDTO;

final class SubscriptionAddonRequestDTO extends BaseDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly float $amount,
        public readonly string $description,
        public readonly int $quantity = 1,
        public readonly bool|null $taxable = null,
        public readonly int|null $serviceId = null,
        public readonly int|null $creditTo = null,
        public readonly int|null $officeId = null,
    ) {
    }
}
