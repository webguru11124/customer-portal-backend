<?php

declare(strict_types=1);

namespace App\Interfaces\Subscription;

interface SubscriptionAware
{
    public function getSubscriptionId(): int;

    public function getOfficeId(): int;
}
