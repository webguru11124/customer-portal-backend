<?php

namespace Tests\Unit\Listeners;

use App\DTO\Customer\SearchCustomersDTO;
use App\Events\Payment\PaymentMade;
use App\Events\PaymentMethod\AchAdded;
use App\Events\PaymentMethod\CcAdded;
use App\Interfaces\AccountNumberAware;
use App\Listeners\ForgetCustomerData;
use App\Models\Account;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesCustomerRepository;
use Illuminate\Cache\TaggedCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Predis\NotSupportedException;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class ForgetCustomerDataTest extends TestCase
{
    use RandomIntTestData;

    protected ForgetCustomerData $listener;

    protected Collection $userAccountsCollection;

    public function setUp(): void
    {
        parent::setUp();

        $this->userAccountsCollection = (new Collection())->add(new Account([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]));

        $this->listener = new ForgetCustomerData($this->userAccountsCollection);
    }

    public function test_it_listens_events(): void
    {
        Event::fake();

        Event::assertListening(AchAdded::class, $this->listener::class);
        Event::assertListening(CcAdded::class, $this->listener::class);
        Event::assertListening(PaymentMade::class, $this->listener::class);
    }

    /**
     * @dataProvider eventsDataProvider
     */
    public function test_it_empties_payment_account_data_cache($event): void
    {
        $taggedCacheMock = \Mockery::mock(TaggedCache::class);
        $taggedCacheMock->shouldReceive('forget')
            ->with(CachedPestRoutesCustomerRepository::buildKey('search', [
                new SearchCustomersDTO(
                    officeIds: $this->listener->getUserAccounts()->pluck('office_id')->toArray(),
                    accountNumbers: $this->listener->getUserAccounts()->pluck('account_number')->toArray(),
                    isActive: true,
                ),
            ]))
            ->once()
            ->andReturn(true);

        Cache::shouldReceive('tags')
            ->with(CachedPestRoutesCustomerRepository::getHashTag('search'))
            ->once()
            ->andReturn($taggedCacheMock);

        $taggedCacheMock = \Mockery::mock(TaggedCache::class);
        $taggedCacheMock->shouldReceive('forget')
            ->with(CachedPestRoutesCustomerRepository::buildKey('find', [$event->getAccountNumber()]))
            ->once()
            ->andReturn(true);

        Cache::shouldReceive('tags')
            ->with(CachedPestRoutesCustomerRepository::getHashTag('find'))
            ->once()
            ->andReturn($taggedCacheMock);

        $this->listener->handle($event);
    }

    /**
     * @dataProvider eventsDataProvider
     */
    public function test_it_logs_redis_exception(AccountNumberAware $event): void
    {
        $errorMessage = 'Error';
        Log::shouldReceive('error')
            ->withArgs(fn (string $message) => str_contains($message, $errorMessage))
            ->andReturn(null);

        $taggedCacheMock = \Mockery::mock(TaggedCache::class);
        $taggedCacheMock->shouldReceive('forget')
            ->with(CachedPestRoutesCustomerRepository::buildKey('search', [
                new SearchCustomersDTO(
                    officeIds: $this->listener->getUserAccounts()->pluck('office_id')->toArray(),
                    accountNumbers: $this->listener->getUserAccounts()->pluck('account_number')->toArray(),
                    isActive: true,
                ),
            ]))
            ->once()
            ->andThrow(new NotSupportedException($errorMessage));

        Cache::shouldReceive('tags')
            ->with(CachedPestRoutesCustomerRepository::getHashTag('search'))
            ->once()
            ->andReturn($taggedCacheMock);

        $this->listener->handle($event);
    }

    /**
     * @return iterable<int, AccountNumberAware>
     */
    public function eventsDataProvider(): iterable
    {
        yield [new AchAdded($this->getTestAccountNumber())];
        yield [new CcAdded($this->getTestAccountNumber())];
        yield [new PaymentMade($this->getTestAccountNumber(), 0)];
    }

    public function test_it_initialize_empty_auth_user_accounts(): void
    {
        $this->assertSame(null, (new ForgetCustomerData())->getUserAccounts());
    }
}
