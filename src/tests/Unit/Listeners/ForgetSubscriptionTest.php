<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\Subscription\SubscriptionStatusChange;
use App\Interfaces\Subscription\SubscriptionStatusChangeAware;
use App\Listeners\ForgetSubscription;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesCustomerRepository;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesServiceTypeRepository;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesSubscriptionRepository;
use Illuminate\Cache\TaggedCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Predis\NotSupportedException;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class ForgetSubscriptionTest extends TestCase
{
    use RandomIntTestData;

    protected ForgetSubscription $listener;

    protected Collection $userAccountsCollection;

    public function setUp(): void
    {
        parent::setUp();

        $this->listener = new ForgetSubscription();
    }

    public function test_it_listens_events(): void
    {
        Event::fake();

        Event::assertListening(SubscriptionStatusChange::class, $this->listener::class);
    }

    /**
     * @dataProvider eventsDataProvider
     */
    public function test_it_reset_cached_subscriptions(SubscriptionStatusChangeAware $event): void
    {
        $taggedCacheMock = \Mockery::mock(TaggedCache::class);
        $taggedCacheMock
            ->shouldReceive('forget')
            ->with(CachedPestRoutesSubscriptionRepository::buildKey('searchByCustomerId', [[$event->getAccountNumber()]]))
            ->once()
            ->andReturns(true);

        Cache::shouldReceive('tags')
            ->with(CachedPestRoutesSubscriptionRepository::getHashTag('searchByCustomerId'))
            ->once()
            ->andReturn($taggedCacheMock);

        $taggedCacheMock = \Mockery::mock(TaggedCache::class);
        $taggedCacheMock
            ->shouldReceive('forget')
            ->with(CachedPestRoutesSubscriptionRepository::buildKey('searchBy', ['customerId', [$event->getAccountNumber()]]))
            ->once()
            ->andReturns(true);

        Cache::shouldReceive('tags')
            ->with(CachedPestRoutesSubscriptionRepository::getHashTag('searchBy'))
            ->once()
            ->andReturn($taggedCacheMock);

        $taggedCacheMock = \Mockery::mock(TaggedCache::class);
        $taggedCacheMock
            ->shouldReceive('forget')
            ->with(CachedPestRoutesCustomerRepository::buildKey('find', [$event->getAccountNumber()]))
            ->once()
            ->andReturn(true);

        Cache::shouldReceive('tags')
            ->with(CachedPestRoutesCustomerRepository::getHashTag('find'))
            ->once()
            ->andReturn($taggedCacheMock);

        $taggedCacheMock = \Mockery::mock(TaggedCache::class);
        $taggedCacheMock
            ->shouldReceive('forget')
            ->with(CachedPestRoutesServiceTypeRepository::buildKey('searchBy', ['customerId', [$event->getAccountNumber()]]))
            ->once()
            ->andReturn(true);

        Cache::shouldReceive('tags')
            ->with(CachedPestRoutesServiceTypeRepository::getHashTag('searchBy'))
            ->once()
            ->andReturn($taggedCacheMock);

        $this->listener->handle($event);
    }

    /**
     * @dataProvider eventsDataProvider
     */
    public function test_it_logs_redis_exception(SubscriptionStatusChangeAware $event): void
    {
        $errorMessage = 'Error';
        Log::shouldReceive('error')
            ->withArgs(fn (string $message) => str_contains($message, $errorMessage))
            ->andReturn(null);

        $taggedCacheMock = \Mockery::mock(TaggedCache::class);
        $taggedCacheMock
            ->expects('forget')
            ->with(CachedPestRoutesSubscriptionRepository::buildKey('searchByCustomerId', [[$event->getAccountNumber()]]))
            ->andThrow(new NotSupportedException($errorMessage));

        Cache::shouldReceive('tags')
            ->with(CachedPestRoutesSubscriptionRepository::getHashTag('searchByCustomerId'))
            ->once()
            ->andReturn($taggedCacheMock);

        $this->listener->handle($event);
    }

    /**
     * @return iterable<int, SubscriptionStatusChangeAware>
     */
    public function eventsDataProvider(): iterable
    {
        yield [new SubscriptionStatusChange(
            subscriptionId: $this->getTestSubscriptionId(),
            accountNumber: $this->getTestAccountNumber(),
            officeId: $this->getTestOfficeId()
        )];
    }
}
