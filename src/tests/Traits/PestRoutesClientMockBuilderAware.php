<?php

namespace Tests\Traits;

use Tests\Builders\PestRoutesClientMockBuilder;

trait PestRoutesClientMockBuilderAware
{
    public function getPestRoutesClientMockBuilder(): PestRoutesClientMockBuilder
    {
        return new PestRoutesClientMockBuilder();
    }
}
