<?php

namespace App\Exceptions\PaymentProfile;

use Exception;

class PaymentProfileIsEmptyException extends Exception
{
    /** @var string */
    protected $message = 'Payment profile is empty';
}
