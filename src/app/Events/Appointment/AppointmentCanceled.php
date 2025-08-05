<?php

declare(strict_types=1);

namespace App\Events\Appointment;

use App\Infra\Metrics\TrackedEvent;
use App\Infra\Metrics\TrackedEventName;
use App\Interfaces\AccountNumberAware;
use Illuminate\Foundation\Events\Dispatchable;

final class AppointmentCanceled implements AccountNumberAware, TrackedEvent
{
    use Dispatchable;

    public function __construct(
        public readonly int $accountNumber
    ) {
    }

    public function getAccountNumber(): int
    {
        return $this->accountNumber;
    }

    public function getEventName(): TrackedEventName
    {
        return TrackedEventName::AppointmentCanceled;
    }

    public function getPayload(): array
    {
        return [
            'accountNumber' => $this->accountNumber,
        ];
    }
}
