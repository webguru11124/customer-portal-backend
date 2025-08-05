<?php

namespace Tests\Unit\Services;

use App\Events\CustomerSessionStarted;
use App\Services\CustomerSessionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class CustomerSessionServiceTest extends TestCase
{
    use RandomIntTestData;

    private const SESSION_PREFIX = 'CUSTOMER_SESSION_';
    private const SESSION_TTL = 300; //5 min

    protected CustomerSessionService $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new CustomerSessionService();
        Event::fake();
    }

    public function test_it_starts_session_if_not_started_yet(): void
    {
        $accountNumber = $this->getTestAccountNumber();

        Cache::shouldReceive('get')
            ->withArgs([$this->buildKey($accountNumber)])
            ->once()
            ->andReturn(null);

        Cache::shouldReceive('set')
            ->withArgs([
                $this->buildKey($accountNumber),
                true,
                self::SESSION_TTL,
            ])
            ->once()
            ->andReturn(true);

        $this->subject->handleSession($this->getTestAccountNumber());

        Event::assertDispatched(CustomerSessionStarted::class);
    }

    public function test_it_updates_customer_session_if_already_started(): void
    {
        $accountNumber = $this->getTestAccountNumber();

        Cache::shouldReceive('get')
            ->withArgs([$this->buildKey($accountNumber)])
            ->once()
            ->andReturn(true);

        Cache::shouldReceive('set')
            ->withArgs([
                $this->buildKey($accountNumber),
                true,
                self::SESSION_TTL,
            ])
            ->once()
            ->andReturn(true);

        $this->subject->handleSession($this->getTestAccountNumber());

        Event::assertNotDispatched(CustomerSessionStarted::class);
    }

    private function buildKey(int $accountNumber): string
    {
        return self::SESSION_PREFIX . $accountNumber;
    }
}
