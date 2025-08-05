<?php

namespace App\Exceptions\PaymentProfile;

use Exception;

class PaymentProfilesNotFoundException extends Exception
{
    /** @var string */
    protected $message = 'Payment profiles not found';
}
