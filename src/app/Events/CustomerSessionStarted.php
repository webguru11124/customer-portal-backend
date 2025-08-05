<?php

declare(strict_types=1);

namespace App\Events;

use App\Interfaces\AccountNumberAware;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerSessionStarted implements AccountNumberAware
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $accountNumber
    ) {
    }

    public function getAccountNumber(): int
    {
        return $this->accountNumber;
    }
}
