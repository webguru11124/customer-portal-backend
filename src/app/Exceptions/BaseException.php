<?php

namespace App\Exceptions;

use Exception;

/**
 * Base Exception.
 */
class BaseException extends Exception
{
    public function getCustomerMessage(): string
    {
        return config('app.default_error_message', 'Error');
    }
}
