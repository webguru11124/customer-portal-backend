<?php

namespace App\Exceptions\TransactionSetup;

use Exception;

class TransactionSetupExpiredException extends Exception
{
    /** @var string */
    protected $message = 'Transaction setup expired';
}
