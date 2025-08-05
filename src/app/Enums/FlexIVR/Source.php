<?php

declare(strict_types=1);

namespace App\Enums\FlexIVR;

enum Source: string
{
    case CUSTOMER_PORTAL = 'CXP';
    case FLEX_IVR = 'IVR';
    case SELF_CHECKOUT = 'SCO';
    case FLEX_SMS = 'SMS';
}
