<?php

namespace Tests\Traits;

use Illuminate\Cache\TaggedCache;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;

trait MockTaggedCache
{
    public function mockTaggedCache(array $tags, int $times = 1): TaggedCache|MockInterface
    {
        $taggedCacheMock = Mockery::mock(TaggedCache::class);

        Cache::shouldReceive('tags')
            ->with($tags)
            ->andReturn($taggedCacheMock)
            ->times($times);

        return $taggedCacheMock;
    }
}
