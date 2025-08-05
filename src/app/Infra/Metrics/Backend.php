<?php

declare(strict_types=1);

namespace App\Infra\Metrics;

interface Backend
{
    public function storeEvent(EventPayload $eventPayload): void;
}
