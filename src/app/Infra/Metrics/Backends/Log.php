<?php

declare(strict_types=1);

namespace App\Infra\Metrics\Backends;

use App\Infra\Metrics\Backend;
use App\Infra\Metrics\EventPayload;
use Illuminate\Support\Facades\Log as LogFacade;

class Log implements Backend
{
    public function storeEvent(EventPayload $eventPayload): void
    {
        LogFacade::debug(sprintf('Metrics event: %s', json_encode($eventPayload)));
    }
}
