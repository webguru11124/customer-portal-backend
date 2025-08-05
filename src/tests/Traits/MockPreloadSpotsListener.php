<?php

namespace Tests\Traits;

use App\Listeners\PreloadSpots;
use Mockery;

trait MockPreloadSpotsListener
{
    private function mockPreloadSpotsListener(): void
    {
        $listener = Mockery::mock(PreloadSpots::class);
        $listener->shouldReceive('handle')
            ->once()
            ->andReturn(null);

        $this->instance(PreloadSpots::class, $listener);
    }

    abstract protected function instance($abstract, $instance);
}
