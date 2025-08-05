<?php

declare(strict_types=1);

namespace App\Repositories\PestRoutes\ParametersFactories;

use TypeError;

abstract class AbstractPestRoutesHttpParametersFactory implements PestRoutesHttpParametersFactory
{
    protected function validateInput(string $expectedClass, object $givenInput): void
    {
        if (!$givenInput instanceof $expectedClass) {
            throw new TypeError(sprintf(
                'Invalid argument type given in %s. %s is expected but %s is given.',
                static::class,
                $expectedClass,
                $givenInput::class
            ));
        }
    }
}
