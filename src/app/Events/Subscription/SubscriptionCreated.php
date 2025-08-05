<?php

declare(strict_types=1);

namespace App\Events\Subscription;

use App\DTO\Subscriptions\SubscriptionAddonRequestDTO;
use App\Infra\Metrics\TrackedEventName;
use App\Interfaces\Subscription\SubscriptionFlagAware;
use App\Interfaces\Subscription\SubscriptionInitialAddonsAware;
use App\Interfaces\Subscription\SubscriptionRecurringAddonsAware;
use Illuminate\Foundation\Events\Dispatchable;

final class SubscriptionCreated implements
    SubscriptionFlagAware,
    SubscriptionInitialAddonsAware,
    SubscriptionRecurringAddonsAware
{
    use Dispatchable;

    /**
     * @param int $subscriptionId
     * @param int $officeId
     * @param int|null $subscriptionFlag
     * @param SubscriptionAddonRequestDTO[] $initialAddons
     * @param SubscriptionAddonRequestDTO[] $recurringAddons
     */
    public function __construct(
        public readonly int $subscriptionId,
        public readonly int $officeId,
        public readonly int|null $subscriptionFlag = null,
        public readonly array $initialAddons = [],
        public readonly array $recurringAddons = []
    ) {
    }

    public function getSubscriptionId(): int
    {
        return $this->subscriptionId;
    }

    public function getOfficeId(): int
    {
        return $this->officeId;
    }

    public function getSubscriptionFlag(): int|null
    {
        return $this->subscriptionFlag;
    }

    public function getSubscriptionInitialAddons(): array
    {
        return $this->initialAddons;
    }

    public function getSubscriptionRecurringAddons(): array
    {
        return $this->recurringAddons;
    }

    public function getEventName(): TrackedEventName
    {
        return TrackedEventName::SubscriptionCreated;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return [
            'subscriptionId' => $this->subscriptionId,
            'officeId' => $this->officeId,
            'subscriptionFlag' => $this->subscriptionFlag,
            'initialAddons' => $this->initialAddons,
            'addons' => $this->recurringAddons,
        ];
    }
}
