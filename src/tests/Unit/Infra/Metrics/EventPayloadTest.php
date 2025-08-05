<?php

declare(strict_types=1);

namespace Tests\Unit\Infra\Metrics;

use App\Infra\Metrics\EventPayload;
use App\Infra\Metrics\TrackedEventName;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

final class EventPayloadTest extends TestCase
{
    private const TIMESTAMP = 1234567890;

    public function test_payload_serialization(): void
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(self::TIMESTAMP));

        $payload = new EventPayload(
            eventName: TrackedEventName::PaymentMade,
            data: ['foo' => 'bar'],
        );

        $this->assertSame(
            '{"date_created":"2009-02-13T23:31:30.000+00:00","from":"backend","product":"customer_portal","name":"billing\/made_one_time_payment","data":{"foo":"bar"}}',
            json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }
}
