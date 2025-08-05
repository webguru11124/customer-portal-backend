<?php

declare(strict_types=1);

namespace Tests\Unit\Events\Subscription;

use App\Events\Subscription\SubscriptionStatusChange;
use App\Infra\Metrics\TrackedEventName;
use PHPUnit\Framework\TestCase;
use Tests\Traits\RandomIntTestData;

final class SubscriptionStatusChangeTest extends TestCase
{
    use RandomIntTestData;

    public function test_getters(): void
    {
        $event = new SubscriptionStatusChange(
            subscriptionId: $this->getTestSubscriptionId(),
            accountNumber: $this->getTestAccountNumber(),
            officeId: $this->getTestOfficeId(),
        );

        $this->assertSame($this->getTestSubscriptionId(), $event->getSubscriptionId());
        $this->assertSame($this->getTestAccountNumber(), $event->getAccountNumber());
        $this->assertSame($this->getTestOfficeId(), $event->getOfficeId());
        $this->assertSame(TrackedEventName::SubscriptionStatusChange, $event->getEventName());
        $this->assertSame(
            [
                'subscriptionId' => $event->getSubscriptionId(),
                'accountNumber' => $event->getAccountNumber(),
                'officeId' => $event->getOfficeId(),
            ],
            $event->getPayload()
        );
    }
}
