<?php

declare(strict_types=1);

namespace App\DTO\Subscriptions;

use Aptive\PestRoutesSDK\Resources\Subscriptions\SubscriptionDay;

final class CreateSubscriptionRequestDTO
{
    public function __construct(
        public readonly int $serviceId,
        public readonly int $customerId,
        public readonly int|null $billToAccountId = null,
        public readonly int|null $billingFrequency = -1,
        public readonly int|null $frequency = 90,
        public readonly int|null $followupDelay = 90,
        public readonly int|null $duration = -1,
        public readonly int|null $agreementLength = null,
        public readonly int|null $preferredEmployeeId = null,
        public readonly int|null $sourceId = null,
        public readonly int|null $regionId = null,
        public readonly int|null $renewalFrequency = null,
        public readonly int|null $soldById = null,
        public readonly int|null $soldBy2Id = null,
        public readonly int|null $soldBy3Id = null,
        public readonly int|null $serviceCharge = null,
        public readonly int|null $customScheduleId = null,
        public readonly int|null $initialCharge = null,
        public readonly bool|null $isActive = true,
        public readonly string|null $subscriptionLink = null,
        public readonly string|null $preferredStart = null,
        public readonly string|null $preferredEnd = null,
        /** @var array<string, mixed> $addOns */
        public readonly array $addOns = [],
        /** @var array<string, mixed> $initialAddons */
        public readonly array $initialAddons = [],
        public readonly \DateTimeInterface|null $renewalDate = null,
        public readonly \DateTimeInterface|null $customDate = null,
        public readonly \DateTimeInterface|null $nextBillingDate = null,
        public readonly SubscriptionDay|null $preferredDay = null,
        public readonly int|null $officeId = null,
        public readonly int|null $flag = null,
    ) {
    }
}
