<?php

namespace Tests\Unit\Listeners;

use App\Events\Appointment\AppointmentCanceled;
use App\Events\Appointment\AppointmentScheduled;
use App\Interfaces\AccountNumberAware;
use App\Interfaces\Repository\CustomerRepository;
use App\Listeners\FlushSpotsCache;
use App\Models\Account;
use App\Models\External\CustomerModel;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesSpotRepository;
use App\Services\AccountService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Predis\NotSupportedException;
use Tests\Data\CustomerData;
use Tests\TestCase;
use Tests\Traits\MockTaggedCache;
use Tests\Traits\RandomIntTestData;

class FlushSpotsCacheTest extends TestCase
{
    use RandomIntTestData;
    use MockTaggedCache;

    protected MockInterface|AccountService $accountServiceMock;
    protected MockInterface|CustomerRepository $customerRepositoryMock;
    protected FlushSpotsCache $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->accountServiceMock = Mockery::mock(AccountService::class);
        $this->customerRepositoryMock = Mockery::mock(CustomerRepository::class);

        $this->subject = new FlushSpotsCache(
            $this->customerRepositoryMock,
            $this->accountServiceMock
        );
    }

    public function test_it_listens_events(): void
    {
        Event::fake();

        Event::assertListening(AppointmentCanceled::class, $this->subject::class);
        Event::assertListening(AppointmentScheduled::class, $this->subject::class);
    }

    /**
     * @dataProvider eventsDataProvider
     */
    public function test_it_flushes_spots_cache(AccountNumberAware $event): void
    {
        $accountNumber = $event->getAccountNumber();

        $account = new Account([
            'account_number' => $accountNumber,
            'office_id' => $this->getTestOfficeId(),
        ]);

        $this->accountServiceMock
            ->shouldReceive('getAccountByAccountNumber')
            ->withArgs([$accountNumber])
            ->once()
            ->andReturn($account);

        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->withArgs([$account->office_id])
            ->once()
            ->andReturn($this->customerRepositoryMock);

        /** @var CustomerModel $customer */
        $customer = CustomerData::getTestEntityData(1, [
            'customerID' => $account->account_number,
            'officeID' => $account->office_id,
        ])->first();

        $this->customerRepositoryMock
            ->shouldReceive('find')
            ->withArgs([$account->account_number])
            ->once()
            ->andReturn($customer);

        $tags = [CachedPestRoutesSpotRepository::buildSearchTag(
            $customer->latitude,
            $customer->longitude
        )];
        $taggedCacheMock = $this->mockTaggedCache($tags);
        $taggedCacheMock->shouldReceive('flush')
            ->once()
            ->andReturn(true);

        $this->subject->handle($event);
    }

    /**
     * @return iterable<int, AccountNumberAware>
     */
    public function eventsDataProvider(): iterable
    {
        yield [new AppointmentScheduled($this->getTestAccountNumber())];
        yield [new AppointmentCanceled($this->getTestAccountNumber())];
    }

    public function test_it_does_not_throw_an_exception_if_cache_throws(): void
    {
        $account = new Account([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]);

        $this->accountServiceMock
            ->shouldReceive('getAccountByAccountNumber')
            ->andReturn($account);

        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->andReturn($this->customerRepositoryMock);

        /** @var CustomerModel $customer */
        $customer = CustomerData::getTestEntityData()->first();

        $this->customerRepositoryMock
            ->shouldReceive('find')
            ->andReturn($customer);

        $tags = [CachedPestRoutesSpotRepository::buildSearchTag($customer->latitude, $customer->longitude)];
        $taggedCacheMock = $this->mockTaggedCache($tags);

        $errorMessage = 'fakeError';
        Log::shouldReceive('error')
            ->withArgs(fn (string $message) => str_contains($message, $errorMessage))
            ->andReturn(null);

        $taggedCacheMock->shouldReceive('flush')
            ->andThrow(new NotSupportedException($errorMessage));

        $event = Mockery::mock(AccountNumberAware::class);
        $event->shouldReceive('getAccountNumber')
            ->andReturn($this->getTestAccountNumber());

        $this->subject->handle($event);
    }
}
