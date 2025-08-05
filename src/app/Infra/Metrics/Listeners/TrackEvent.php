<?php

declare(strict_types=1);

namespace App\Infra\Metrics\Listeners;

use App\Infra\Metrics\Backend;
use App\Infra\Metrics\EventPayload;
use App\Infra\Metrics\TrackedEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class TrackEvent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(private readonly Backend $backend)
    {
    }

    /**
     * Handle the event.
     */
    public function handle(TrackedEvent $event): void
    {
        $payload = new EventPayload($event->getEventName(), $event->getPayload());

        $this->backend->storeEvent($payload);
    }
}
