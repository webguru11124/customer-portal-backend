<?php

declare(strict_types=1);

namespace App\Interfaces\Subscription;

interface SubscriptionFlagAware extends SubscriptionAware
{
    public function getSubscriptionFlag(): int|null;
}
