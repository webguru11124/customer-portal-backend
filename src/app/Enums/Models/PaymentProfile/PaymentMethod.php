<?php

declare(strict_types=1);

namespace App\Enums\Models\PaymentProfile;

enum PaymentMethod: string
{
    case CREDIT_CARD = 'CC';
    case ACH = 'ACH';
}
