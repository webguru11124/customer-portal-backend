<?php

namespace Tests\Traits;

use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Exceptions\ResourceNotFoundException;

trait PestroutesSdkExceptionProvider
{
    public function pestroutesSdkExceptionProvider(): array
    {
        return [
            [new ResourceNotFoundException()],
            [new InternalServerErrorHttpException()],
        ];
    }
}
