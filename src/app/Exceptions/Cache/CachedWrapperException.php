<?php

namespace App\Exceptions\Cache;

use Exception;

class CachedWrapperException extends Exception
{
    public static function wrappedClassImplementationMismatch(): self
    {
        return new self('Cached wrapper and wrapped class should implement same interfaces');
    }
}
