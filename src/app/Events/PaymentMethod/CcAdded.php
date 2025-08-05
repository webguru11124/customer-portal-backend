<?php

declare(strict_types=1);

namespace App\Events\PaymentMethod;

use App\Infra\Metrics\TrackedEvent;
use App\Infra\Metrics\TrackedEventName;
use App\Interfaces\AccountNumberAware;
use Illuminate\Foundation\Events\Dispatchable;

final class CcAdded implements TrackedEvent, AccountNumberAware
{
    use Dispatchable;

    public function __construct(
        private readonly int $accountNumber
    ) {
    }

    public function getEventName(): TrackedEventName
    {
        return TrackedEventName::CcPaymentMethodAdded;
    }

    public function getAccountNumber(): int
    {
        return $this->accountNumber;
    }

    public function getPayload(): array
    {
        return [
            'accountNumber' => $this->accountNumber,
        ];
    }
}
