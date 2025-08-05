<?php

namespace App\Traits;

use Aptive\Component\JsonApi\Exceptions\ValidationException;

trait ValidateObjectClass
{
    /**
     * Validates if class is the same as expected.
     */
    protected function validateObjectClass(object $object, string $expectedClass): void
    {
        if ($object instanceof $expectedClass) {
            return;
        }

        throw new ValidationException(sprintf(
            'Expected %s class but %s given.',
            $expectedClass,
            $object::class
        ));
    }
}
