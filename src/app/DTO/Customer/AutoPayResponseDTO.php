<?php

declare(strict_types=1);

namespace App\DTO\Customer;

use DateTimeInterface;

final class AutoPayResponseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly bool $isEnabled,
        public readonly string|null $planName = null,
        public readonly float|null $nextPaymentAmount = null,
        public readonly DateTimeInterface|null $nextPaymentDate = null,
        public readonly string|null $cardType = null,
        public readonly string|null $cardLastFour = null,
        public readonly string|null $preferredBillingDate = null,
    ) {
    }
}
