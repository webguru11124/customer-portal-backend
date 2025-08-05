<?php

namespace App\Infra\Metrics;

interface TrackedEvent
{
    public function getEventName(): TrackedEventName;

    /**
     * @return array{accountNumber: int}
     */
    public function getPayload(): array;
}
