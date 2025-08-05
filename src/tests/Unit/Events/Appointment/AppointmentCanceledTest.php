<?php

declare(strict_types=1);

namespace Tests\Unit\Events\Appointment;

use App\Events\Appointment\AppointmentCanceled;
use App\Infra\Metrics\TrackedEventName;
use PHPUnit\Framework\TestCase;
use Tests\Traits\RandomIntTestData;

final class AppointmentCanceledTest extends TestCase
{
    use RandomIntTestData;

    public function test_getters(): void
    {
        $event = new AppointmentCanceled($this->getTestAccountNumber());

        $this->assertSame($this->getTestAccountNumber(), $event->getAccountNumber());
        $this->assertSame(['accountNumber' => $this->getTestAccountNumber()], $event->getPayload());
        $this->assertSame(TrackedEventName::AppointmentCanceled, $event->getEventName());
    }
}
