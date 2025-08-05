<?php

declare(strict_types=1);

namespace Tests\Unit\Events\PaymentMethod;

use App\Events\PaymentMethod\CcAdded;
use App\Infra\Metrics\TrackedEventName;
use PHPUnit\Framework\TestCase;
use Tests\Traits\RandomIntTestData;

final class CcAddedTest extends TestCase
{
    use RandomIntTestData;

    public function test_getters(): void
    {
        $event = new CcAdded($this->getTestAccountNumber());

        $this->assertSame($this->getTestAccountNumber(), $event->getAccountNumber());
        $this->assertSame(['accountNumber' => $this->getTestAccountNumber()], $event->getPayload());
        $this->assertSame(TrackedEventName::CcPaymentMethodAdded, $event->getEventName());
    }
}
