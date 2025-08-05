<?php

namespace App\Enums\Models\Customer;

enum AutoPay: string
{
    case NO = 'No';
    case CREDIT_CARD = 'CC';
    case ACH = 'ACH';

    /**
     * Check if enum is not NO type.
     *
     * @return bool
     */
    public function isAutoPay(): bool
    {
        return $this !== self::NO;
    }
}
