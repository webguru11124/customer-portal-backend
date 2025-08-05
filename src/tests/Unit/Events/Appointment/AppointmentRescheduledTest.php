<?php

declare(strict_types=1);

namespace Tests\Unit\Events\Appointment;

use App\Events\Appointment\AppointmentRescheduled;
use App\Infra\Metrics\TrackedEventName;
use PHPUnit\Framework\TestCase;
use Tests\Traits\RandomIntTestData;

final class AppointmentRescheduledTest extends TestCase
{
    use RandomIntTestData;

    public function test_getters(): void
    {
        $event = new AppointmentRescheduled($this->getTestAccountNumber());

        $this->assertSame($this->getTestAccountNumber(), $event->getAccountNumber());
        $this->assertSame(['accountNumber' => $this->getTestAccountNumber()], $event->getPayload());
        $this->assertSame(TrackedEventName::AppointmentRescheduled, $event->getEventName());
    }
}
