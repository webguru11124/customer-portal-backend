<?php

namespace Tests\Traits;

use App\Listeners\FlushSpotsCache;
use Mockery;

trait MockFlushSpotsCacheListener
{
    private function mockFlushSpotsCacheListener(): void
    {
        $listener = Mockery::mock(FlushSpotsCache::class);
        $listener->shouldReceive('handle')
            ->once()
            ->andReturn(null);

        $this->instance(FlushSpotsCache::class, $listener);
    }

    abstract protected function instance($abstract, $instance);
}
