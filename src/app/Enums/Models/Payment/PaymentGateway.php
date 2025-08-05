<?php

declare(strict_types=1);

namespace App\Enums\Models\Payment;

enum PaymentGateway: int
{
    case PAYMENT_GATEWAY_WORLDPAY_ID = 1;
    case PAYMENT_GATEWAY_WORLDPAY_TOKENEX_TRANSPARENT_ID = 2;
    case PAYMENT_GATEWAY_TOKENEX_ID = 3;
}
