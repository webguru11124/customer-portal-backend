<?php

declare(strict_types=1);

namespace App\Events\Payment;

use App\Infra\Metrics\TrackedEvent;
use App\Infra\Metrics\TrackedEventName;
use App\Interfaces\AccountNumberAware;
use Illuminate\Foundation\Events\Dispatchable;

final class PaymentMade implements TrackedEvent, AccountNumberAware
{
    use Dispatchable;

    public function __construct(
        private readonly int $accountNumber,
        public readonly int $quantity,
    ) {
    }

    public function getEventName(): TrackedEventName
    {
        return TrackedEventName::PaymentMade;
    }

    public function getAccountNumber(): int
    {
        return $this->accountNumber;
    }

    public function getPayload(): array
    {
        return [
            'accountNumber' => $this->accountNumber,
            'quantity' => $this->quantity,
        ];
    }
}
