<?php

declare(strict_types=1);

namespace Tests\Unit\Events\Appointment;

use App\Events\Appointment\AppointmentScheduled;
use App\Infra\Metrics\TrackedEventName;
use PHPUnit\Framework\TestCase;
use Tests\Traits\RandomIntTestData;

final class AppointmentScheduledTest extends TestCase
{
    use RandomIntTestData;

    public function test_getters(): void
    {
        $event = new AppointmentScheduled($this->getTestAccountNumber());

        $this->assertSame($this->getTestAccountNumber(), $event->getAccountNumber());
        $this->assertSame(['accountNumber' => $this->getTestAccountNumber()], $event->getPayload());
        $this->assertSame(TrackedEventName::AppointmentScheduled, $event->getEventName());
    }
}
