<?php

declare(strict_types=1);

namespace App\Exceptions\Account;

use Aptive\Component\Http\Exceptions\AbstractHttpException;
use Aptive\Component\Http\HttpStatus;

class CleoCrmAccountNotFoundException extends AbstractHttpException
{
    public const STATUS_CODE = HttpStatus::NOT_FOUND;
}
