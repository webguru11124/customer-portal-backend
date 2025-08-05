<?php

namespace App\Exceptions\PestRoutesRepository;

use Exception;

class OfficeNotSetException extends Exception
{
    /** @var string */
    protected $message = 'OfficeId not set';
}
