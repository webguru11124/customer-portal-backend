<?php

declare(strict_types=1);

namespace Tests\Unit\Events\Spot;

use App\Events\Spot\SpotSearched;
use App\Infra\Metrics\TrackedEventName;
use PHPUnit\Framework\TestCase;
use Tests\Traits\RandomIntTestData;

final class SpotSearchedTest extends TestCase
{
    use RandomIntTestData;

    public function test_getters(): void
    {
        $latitude = $this->getRandomLatitude();
        $longitude = $this->getRandomLongitude();
        $event = new SpotSearched($this->getTestAccountNumber(), $latitude, $longitude);
        $this->assertSame($this->getTestAccountNumber(), $event->getAccountNumber());
        $this->assertSame([
            'accountNumber' => $this->getTestAccountNumber(),
            'latitude' => $latitude,
            'longitude' => $longitude
        ], $event->getPayload());
        $this->assertSame(TrackedEventName::SpotSearched, $event->getEventName());
    }

    public function getRandomLatitude(): float
    {
        return mt_rand(-900000, 900000) / 10000;
    }

    public function getRandomLongitude(): float
    {
        return mt_rand(-1800000, 1800000) / 10000;
    }
}
