<?php

namespace App\Enums\PestRoutes\Payment;

enum PaymentMethod: int
{
    case COUPON = 0;
    case CASH = 1;
    case CHECK = 2;
    case CREDIT_CARD = 3;
    case ACH = 4;
}
