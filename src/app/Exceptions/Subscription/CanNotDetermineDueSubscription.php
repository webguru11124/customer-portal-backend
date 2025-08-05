<?php

declare(strict_types=1);

namespace App\Exceptions\Subscription;

use Exception;

class CanNotDetermineDueSubscription extends Exception
{
    /** @var string */
    protected $message = 'Can not determine the subscription customer is due for.';
}
