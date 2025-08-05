<?php

declare(strict_types=1);

namespace App\Enums;

enum SubscriptionStatus: int
{
    case ACTIVE = 1;
    case FROZEN = 0;
}
