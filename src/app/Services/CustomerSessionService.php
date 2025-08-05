<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\CustomerSessionStarted;
use Illuminate\Support\Facades\Cache;

class CustomerSessionService
{
    public const SESSION_PREFIX = 'CUSTOMER_SESSION_';
    private const SESSION_TTL = 300; //5 min

    public function handleSession(int $accountNumber): void
    {
        if (!$this->isSessionAlive($accountNumber)) {
            $this->startSession($accountNumber);

            return;
        }

        $this->updateSession($accountNumber);
    }

    private function isSessionAlive(int $accountNumber): bool
    {
        return (bool) Cache::get($this->buildKey($accountNumber));
    }

    private function startSession(int $accountNumber): void
    {
        Cache::set($this->buildKey($accountNumber), true, self::SESSION_TTL);

        CustomerSessionStarted::dispatch($accountNumber);
    }

    private function updateSession(int $accountNumber): void
    {
        Cache::set($this->buildKey($accountNumber), true, self::SESSION_TTL);
    }

    private function buildKey(int $accountNumber): string
    {
        return self::SESSION_PREFIX . $accountNumber;
    }
}
