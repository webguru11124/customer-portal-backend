<?php

declare(strict_types=1);

namespace App\Exceptions\Entity;

use Exception;

class ExternalModelCanNotBeSearchedByAttribute extends Exception
{
    public function __construct(string $repositoryClass, string $attribute)
    {
        $methodSuffix = ucfirst($attribute);

        $message = sprintf(
            'Method searchBy%s not found in related repository %s.
            External model can not be searched by attribute %s.',
            $methodSuffix,
            $repositoryClass,
            $attribute
        );

        parent::__construct($message);
    }
}
