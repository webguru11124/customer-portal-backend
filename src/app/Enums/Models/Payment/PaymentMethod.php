<?php

namespace App\Enums\Models\Payment;

enum PaymentMethod: string
{
    case CREDIT_CARD = 'CC';
    case ACH = 'ACH';
    case OTHER = 'OTHER';
}
