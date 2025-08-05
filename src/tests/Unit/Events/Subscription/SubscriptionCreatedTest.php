<?php

declare(strict_types=1);

namespace Tests\Unit\Events\Subscription;

use App\DTO\Subscriptions\SubscriptionAddonRequestDTO;
use App\Events\Subscription\SubscriptionCreated;
use App\Infra\Metrics\TrackedEventName;
use PHPUnit\Framework\TestCase;
use Tests\Traits\RandomIntTestData;

final class SubscriptionCreatedTest extends TestCase
{
    use RandomIntTestData;

    public function test_getters(): void
    {
        $addon = new SubscriptionAddonRequestDTO(
            productId: $this->getTestProductId(),
            amount: 199,
            description: 'test description'
        );

        $event = new SubscriptionCreated(
            subscriptionId: $this->getTestSubscriptionId(),
            officeId: $this->getTestOfficeId(),
            subscriptionFlag: $this->getTestSubscriptionFlagId(),
            initialAddons: [$addon],
            recurringAddons: [$addon],
        );

        $this->assertSame($this->getTestSubscriptionId(), $event->getSubscriptionId());
        $this->assertSame($this->getTestOfficeId(), $event->getOfficeId());
        $this->assertSame($this->getTestSubscriptionFlagId(), $event->getSubscriptionFlag());
        $this->assertSame([$addon], $event->getSubscriptionInitialAddons());
        $this->assertSame([$addon], $event->getSubscriptionInitialAddons());
        $this->assertSame(TrackedEventName::SubscriptionCreated, $event->getEventName());
        $this->assertSame(
            [
                'subscriptionId' => $event->getSubscriptionId(),
                'officeId' => $event->getOfficeId(),
                'subscriptionFlag' => $event->getSubscriptionFlag(),
                'initialAddons' => $event->getSubscriptionInitialAddons(),
                'addons' => $event->getSubscriptionRecurringAddons(),
            ],
            $event->getPayload()
        );
    }
}
