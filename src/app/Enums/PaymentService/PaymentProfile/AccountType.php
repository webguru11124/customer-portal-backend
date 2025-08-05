<?php

namespace App\Enums\PaymentService\PaymentProfile;

enum AccountType: string
{
    case PERSONAL_CHECKING = 'personal_checking';
    case PERSONAL_SAVINGS = 'personal_savings';
    case BUSINESS_CHECKING = 'business_checking';
    case BUSINESS_SAVINGS = 'business_savings';
}
