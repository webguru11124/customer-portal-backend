<?php

namespace App\Exceptions\PaymentProfile;

use Exception;

class PaymentProfileNotDeletedException extends Exception
{
    public const STATUS_LOCKED = 1;

    /** @var string */
    protected $message = 'Payment profile was not deleted';
}
