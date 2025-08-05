<?php

declare(strict_types=1);

namespace App\Exceptions\PlanBuilder;

use Exception;

class FieldNotFound extends Exception
{
    /** @var string */
    protected $message = 'Field not found';
}
