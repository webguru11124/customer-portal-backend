<?php

declare(strict_types=1);

namespace Tests\Unit\Infra\Metrics\Listeners;

use App\Events\Payment\PaymentMade;
use App\Infra\Metrics\Backend;
use App\Infra\Metrics\EventPayload;
use App\Infra\Metrics\Listeners\TrackEvent;
use App\Infra\Metrics\ProductName;
use App\Infra\Metrics\SourceName;
use App\Infra\Metrics\TrackedEvent;
use App\Infra\Metrics\TrackedEventName;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\Traits\RandomIntTestData;

final class TrackEventTest extends TestCase
{
    use RandomIntTestData;

    private const EVENT_TIME = 1234567890;
    private const EVENT_QUANTITY = 97;

    /**
     * @dataProvider eventDataProvider
     */
    public function test_it_tracks_event(TrackedEvent $event, array $data): void
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(self::EVENT_TIME));

        $backend = $this->createMock(Backend::class);
        $backend
            ->expects(self::once())
            ->method('storeEvent')
            ->with(self::callback(static function (EventPayload $payload) use ($event, $data): bool {
                return $payload->eventName === $event->getEventName()
                    && $payload->data === $data
                    && $payload->sourceName === SourceName::Backend
                    && $payload->productName === ProductName::CustomerPortal
                    && $payload->dateCreated->timestamp === self::EVENT_TIME;
            }));

        $listener = new TrackEvent($backend);
        $listener->handle($event);

        Carbon::setTestNow(null);
    }

    /**
     * @return iterable<array{TrackedEvent, array<string, string>}>
     */
    public function eventDataProvider(): iterable
    {
        $event = $this->createMock(TrackedEvent::class);
        $event
            ->expects(self::exactly(2))
            ->method('getEventName')
            ->willReturn(TrackedEventName::PaymentMade);

        yield 'Basic event' => [
            $event,
            [],
        ];

        yield 'Event with data' => [
            new PaymentMade($this->getTestAccountNumber(), self::EVENT_QUANTITY),
            ['accountNumber' => $this->getTestAccountNumber(), 'quantity' => self::EVENT_QUANTITY],
        ];
    }
}
