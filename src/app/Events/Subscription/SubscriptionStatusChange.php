<?php

declare(strict_types=1);

namespace App\Events\Subscription;

use App\Infra\Metrics\TrackedEventName;
use App\Interfaces\Subscription\SubscriptionStatusChangeAware;
use Illuminate\Foundation\Events\Dispatchable;

final class SubscriptionStatusChange implements SubscriptionStatusChangeAware
{
    use Dispatchable;

    /**
     * @param int $subscriptionId
     * @param int $accountNumber
     * @param int $officeId
     */
    public function __construct(
        public readonly int $subscriptionId,
        public readonly int $accountNumber,
        public readonly int $officeId,
    ) {
    }

    public function getSubscriptionId(): int
    {
        return $this->subscriptionId;
    }

    public function getAccountNumber(): int
    {
        return $this->accountNumber;
    }

    public function getOfficeId(): int
    {
        return $this->officeId;
    }

    public function getEventName(): TrackedEventName
    {
        return TrackedEventName::SubscriptionStatusChange;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return [
            'subscriptionId' => $this->subscriptionId,
            'accountNumber' => $this->accountNumber,
            'officeId' => $this->officeId,
        ];
    }
}
