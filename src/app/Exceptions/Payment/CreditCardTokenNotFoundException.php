<?php

declare(strict_types=1);

namespace App\Exceptions\Payment;

use Aptive\Component\Http\Exceptions\AbstractHttpException;
use Aptive\Component\Http\HttpStatus;

class CreditCardTokenNotFoundException extends AbstractHttpException
{
    public const STATUS_CODE = HttpStatus::NOT_FOUND;
}
