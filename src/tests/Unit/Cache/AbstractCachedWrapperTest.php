<?php

declare(strict_types=1);

namespace Tests\Unit\Cache;

use App\Cache\AbstractCachedWrapper;
use App\Exceptions\Cache\CachedWrapperException;
use App\Interfaces\AccountNumberAware;
use Countable;
use Illuminate\Cache\TaggedCache;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class AbstractCachedWrapperTest extends TestCase
{
    public const TEST_CACHED_DATA = 9999;
    public const TEST_TTL = 1;

    protected AbstractCachedWrapper $subject;

    private function getValidWrapped(): mixed
    {
        return new class () implements Countable {
            private int $calledTimes = 0;

            public function count(): int
            {
                $this->calledTimes++;

                return AbstractCachedWrapperTest::TEST_CACHED_DATA;
            }

            public function getCalledTimes(): int
            {
                return $this->calledTimes;
            }
        };
    }

    private function getInvalidWrapped(): mixed
    {
        return new class () implements AccountNumberAware {
            public function getAccountNumber(): int
            {
                return random_int(1, 99999);
            }
        };
    }

    private function initSubject(mixed $wrapped)
    {
        $this->subject = new class ($wrapped) extends AbstractCachedWrapper implements Countable {
            public function __construct(mixed $wrapped)
            {
                $this->wrapped = $wrapped;
            }

            protected function getCacheTtl(string $methodName): int
            {
                return AbstractCachedWrapperTest::TEST_TTL;
            }

            public function count(): int
            {
                return $this->cached(__FUNCTION__);
            }
        };
    }

    public function test_it_sets_and_gets_cached_method_result()
    {
        $wrapped = $this->getValidWrapped();
        $this->initSubject($wrapped);

        for ($i = 0; $i < random_int(2, 7); $i++) {
            $result = $this->subject->count();

            self::assertSame(self::TEST_CACHED_DATA, $result);
        }

        self::assertSame(1, $wrapped->getCalledTimes());
    }

    public function test_it_tags_saved_data(): void
    {
        $wrapped = $this->getValidWrapped();
        $this->initSubject($wrapped);

        $tag = 'Test_Tag';

        $taggedCacheMock = Mockery::mock(TaggedCache::class);
        $taggedCacheMock->shouldReceive('remember')
            ->andReturn(self::TEST_CACHED_DATA)
            ->once();

        Cache::shouldReceive('tags')
            ->andReturn($taggedCacheMock)
            ->once();

        $this->subject->tags([$tag])->count();
    }

    public function test_it_updates_cache_when_ttl_is_expired()
    {
        $wrapped = $this->getValidWrapped();
        $this->initSubject($wrapped);

        $this->subject->count();
        sleep(self::TEST_TTL + 1);

        for ($i = 0; $i < random_int(2, 7); $i++) {
            $this->subject->count();
        }

        self::assertSame(2, $wrapped->getCalledTimes());
    }

    public function test_it_throws_exception_if_wrapped_doesnt_implement_all_interfaces_of_wrapper()
    {
        $this->initSubject($this->getInvalidWrapped());

        $this->expectException(CachedWrapperException::class);

        $this->subject->count();
    }
}
