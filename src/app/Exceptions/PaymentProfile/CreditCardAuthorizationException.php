<?php

namespace App\Exceptions\PaymentProfile;

use App\Exceptions\BaseException;

/**
 * Add Credit Card Expection.
 */
class CreditCardAuthorizationException extends BaseException
{
    public const ERROR_MESSAGES = [
        'PAYMENT ACCOUNT NOT FOUND [103]',
        'Payment failed: Not Authorized [105]',
        'Payment failed: Duplicate [23]',
        'INVALID CARD INFO',
        'LOST CARD',
        'STOLEN CARD',
        'Not Authorized',
        'Expired Card',
        'Call Issuer',
        'Declined'
    ];
    //
}
