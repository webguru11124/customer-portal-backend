<?php

declare(strict_types=1);

namespace App\Exceptions\Subscription;

use Exception;

class SubscriptionNotFound extends Exception
{
    /** @var string */
    protected $message = 'Can not find active subscription';
}
