<?php

declare(strict_types=1);

namespace Tests\Unit\Events\Payment;

use App\Events\Payment\PaymentMade;
use App\Infra\Metrics\TrackedEventName;
use PHPUnit\Framework\TestCase;
use Tests\Traits\RandomIntTestData;

final class PaymentMadeTest extends TestCase
{
    use RandomIntTestData;

    public function test_getters(): void
    {
        $amount = random_int(999, 888888);
        $event = new PaymentMade($this->getTestAccountNumber(), $amount);

        $this->assertSame($this->getTestAccountNumber(), $event->getAccountNumber());
        $this->assertSame($amount, $event->quantity);
        $this->assertSame(TrackedEventName::PaymentMade, $event->getEventName());
        $this->assertSame(
            ['accountNumber' => $this->getTestAccountNumber(), 'quantity' => $amount],
            $event->getPayload()
        );
    }
}
