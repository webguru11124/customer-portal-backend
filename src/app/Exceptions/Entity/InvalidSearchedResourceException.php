<?php

declare(strict_types=1);

namespace App\Exceptions\Entity;

use Exception;

class InvalidSearchedResourceException extends Exception
{
    /** @var string */
    protected $message = 'Given resource does not have search method';
}
