<?php

declare(strict_types=1);

namespace App\Interfaces\Subscription;

interface SubscriptionStatusChangeAware extends SubscriptionAware
{
    public function getAccountNumber(): int;
}
