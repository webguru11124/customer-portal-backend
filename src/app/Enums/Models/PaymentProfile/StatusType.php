<?php

declare(strict_types=1);

namespace App\Enums\Models\PaymentProfile;

enum StatusType: string
{
    case DELETED = 'deleted';
    case EMPTY = 'empty';
    case VALID = 'valid';
    case INVALID = 'invalid';
    case EXPIRED = 'expired';
    case FAILED = 'failed';
}
