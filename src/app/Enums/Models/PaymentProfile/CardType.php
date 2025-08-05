<?php

declare(strict_types=1);

namespace App\Enums\Models\PaymentProfile;

enum CardType: string
{
    case VISA = 'VISA';
    case MASTERCARD = 'MASTERCARD';
    case AMEX = 'AMEX';
    case DISCOVER = 'DISCOVER';
    case OTHER = 'OTHER';
}
