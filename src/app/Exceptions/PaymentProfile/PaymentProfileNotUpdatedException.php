<?php

namespace App\Exceptions\PaymentProfile;

use Exception;

class PaymentProfileNotUpdatedException extends Exception
{
    /** @var string */
    protected $message = 'Payment profiles not updated';
}
