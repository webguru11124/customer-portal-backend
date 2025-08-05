<?php

declare(strict_types=1);

namespace Tests\Traits;

trait ExpectedV1ResponseData
{
    use ExpectedResponseData;

    private function getLinkPrefix(): string
    {
        return '/api/v1/';
    }
}
