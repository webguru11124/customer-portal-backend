<?php

declare(strict_types=1);

namespace Tests\Unit\Infra\Metrics\Backends;

use App\Infra\Metrics\Backends\Log;
use App\Infra\Metrics\EventPayload;
use App\Infra\Metrics\TrackedEventName;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log as LogFacade;
use PHPUnit\Framework\TestCase;

class LogTest extends TestCase
{
    public function test_it_logs_event_data(): void
    {
        LogFacade::expects('debug')
            ->withArgs(['Metrics event: {"date_created":"2009-02-13T23:31:30.000+00:00","from":"backend","product":"customer_portal","name":"appointment\/scheduled","data":{"accountNumber":99999999}}'])
            ->once();

        Carbon::setTestNow(Carbon::createFromTimestamp(1234567890));

        $backend = new Log();
        $backend->storeEvent(
            new EventPayload(
                TrackedEventName::AppointmentScheduled,
                [
                    'accountNumber' => 99999999,
                ]
            )
        );

        $this->addToAssertionCount(1);

        Carbon::setTestNow(null);
    }
}
