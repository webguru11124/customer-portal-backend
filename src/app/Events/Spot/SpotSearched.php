<?php

declare(strict_types=1);

namespace App\Events\Spot;

use App\Infra\Metrics\TrackedEvent;
use App\Infra\Metrics\TrackedEventName;
use App\Interfaces\AccountNumberAware;
use Illuminate\Foundation\Events\Dispatchable;

final class SpotSearched implements TrackedEvent, AccountNumberAware
{
    use Dispatchable;

    public function __construct(
        private readonly int $accountNumber,
        public readonly float $latitude,
        public readonly float $longitude
    ) {
    }

    public function getEventName(): TrackedEventName
    {
        return TrackedEventName::SpotSearched;
    }

    public function getAccountNumber(): int
    {
        return $this->accountNumber;
    }

    public function getPayload(): array
    {
        return [
            'accountNumber' => $this->accountNumber,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
