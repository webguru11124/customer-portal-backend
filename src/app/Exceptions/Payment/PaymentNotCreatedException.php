<?php

namespace App\Exceptions\Payment;

use Exception;

class PaymentNotCreatedException extends Exception
{
    /** @var string */
    protected $message = 'Payment not created';
}
