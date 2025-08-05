<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes\Cached;

use App\Cache\AbstractCachedWrapper;
use App\DTO\Subscriptions\ActivateSubscriptionRequestDTO;
use App\DTO\Subscriptions\ActivateSubscriptionResponseDTO;
use App\DTO\Subscriptions\CreateSubscriptionRequestDTO;
use App\DTO\Subscriptions\CreateSubscriptionResponseDTO;
use App\DTO\Subscriptions\DeactivateSubscriptionRequestDTO;
use App\DTO\Subscriptions\DeactivateSubscriptionResponseDTO;
use App\Repositories\AbstractExternalRepository;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesSubscriptionRepository;
use App\Repositories\PestRoutes\PestRoutesSubscriptionRepository;
use App\Repositories\RepositoryContext;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\SubscriptionData;
use Tests\TestCase;
use Tests\Traits\MockTaggedCache;
use Tests\Traits\RandomIntTestData;

class CachedPestRoutesSubscriptionRepositoryTest extends TestCase
{
    use RandomIntTestData;
    use ExtendsAbstractCachedExternalRepositoryWrapper;
    use MockTaggedCache;

    private const TTL_DEFAULT = 300;

    public const CACHE_STORE = 'array';
    public const TTL_PATH = 'cache.custom_ttl.repositories.subscription';

    protected CachedPestRoutesSubscriptionRepository $subject;
    protected MockInterface|PestRoutesSubscriptionRepository $pestRoutesSubscriptionRepositoryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->context = new RepositoryContext();
        $this->pestRoutesSubscriptionRepositoryMock = Mockery::mock(PestRoutesSubscriptionRepository::class);

        $this->subject = Mockery::mock(CachedPestRoutesSubscriptionRepository::class, [
            $this->pestRoutesSubscriptionRepositoryMock,
        ])->shouldAllowMockingProtectedMethods()->makePartial();
    }

    protected function getSubject(): CachedPestRoutesSubscriptionRepository
    {
        return $this->subject;
    }

    protected function getWrappedRepositoryMock(): MockInterface|AbstractExternalRepository
    {
        return $this->pestRoutesSubscriptionRepositoryMock;
    }

    protected function getContext(): RepositoryContext
    {
        return $this->context;
    }

    public function test_it_extends_abstract_cached_wrapper_class()
    {
        self::assertInstanceOf(AbstractCachedWrapper::class, $this->subject);
    }

    /**
     * @dataProvider ttlDataProvider
     */
    public function test_it_provides_proper_ttl(string $methodName, int $ttl)
    {
        $instance = new class ($this->pestRoutesSubscriptionRepositoryMock) extends CachedPestRoutesSubscriptionRepository {
            public function getCacheTtlTest(string $methodName): int
            {
                return parent::getCacheTtl($methodName);
            }
        };

        self::assertSame($ttl, $instance->getCacheTtlTest($methodName));
    }

    /**
     * @return iterable<int, array<int, string|int>>
     */
    public function ttlDataProvider(): iterable
    {
        yield ['find', self::TTL_DEFAULT];
        yield ['search', self::TTL_DEFAULT];
        yield ['searchByCustomerId', self::TTL_DEFAULT];
    }

    protected function getTtlPath(): string
    {
        return self::TTL_PATH;
    }

    public function test_it_caches_search_by_customer_id(): void
    {
        $subscriptions = SubscriptionData::getTestEntityData();

        $tags = [$this->subject::getHashTag('searchByCustomerId')];
        $taggedCacheMock = $this->mockTaggedCache($tags);
        $taggedCacheMock->shouldReceive('remember')
            ->andReturn($subscriptions)
            ->once();

        $this->getWrappedRepositoryMock()
            ->shouldReceive('office')
            ->with($this->getTestOfficeId())
            ->once()
            ->andReturnSelf();

        $result = $this->subject
            ->office($this->getTestOfficeId())
            ->searchByCustomerId([$this->getTestAccountNumber()]);

        self::assertSame($subscriptions, $result);
    }

    public function test_it_does_not_cache_create_subscription(): void
    {
        $subscriptionsRequestDTO = new CreateSubscriptionRequestDTO(
            serviceId: $this->getTestServiceId(),
            customerId: $this->getTestAccountNumber()
        );

        $subscriptionsResponseDTO = new CreateSubscriptionResponseDTO(
            subscriptionId: $this->getTestSubscriptionId()
        );

        Cache::shouldReceive('tags')->never();

        $this->getWrappedRepositoryMock()
            ->shouldReceive('createSubscription')
            ->with($subscriptionsRequestDTO)
            ->once()
            ->andReturn($subscriptionsResponseDTO);

        $result = $this->subject->createSubscription($subscriptionsRequestDTO);

        self::assertSame($subscriptionsResponseDTO, $result);
    }

    public function test_it_does_not_cache_activate_subscription(): void
    {
        $activateSubscriptionsRequestDTO = new ActivateSubscriptionRequestDTO(
            subscriptionId: $this->getTestSubscriptionId(),
            customerId: $this->getTestAccountNumber()
        );

        $subscriptionsResponseDTO = new ActivateSubscriptionResponseDTO(
            subscriptionId: $this->getTestSubscriptionId()
        );

        Cache::shouldReceive('tags')->never();

        $this->getWrappedRepositoryMock()
            ->shouldReceive('activateSubscription')
            ->with($activateSubscriptionsRequestDTO)
            ->once()
            ->andReturn($subscriptionsResponseDTO);

        $result = $this->subject->activateSubscription($activateSubscriptionsRequestDTO);

        self::assertSame($subscriptionsResponseDTO, $result);
    }

    public function test_it_does_not_cache_deactivate_subscription(): void
    {
        $deactivateSubscriptionsRequestDTO = new DeactivateSubscriptionRequestDTO(
            subscriptionId: $this->getTestSubscriptionId(),
            customerId: $this->getTestAccountNumber()
        );

        $subscriptionsResponseDTO = new DeactivateSubscriptionResponseDTO(
            subscriptionId: $this->getTestSubscriptionId()
        );

        Cache::shouldReceive('tags')->never();

        $this->getWrappedRepositoryMock()
            ->shouldReceive('deactivateSubscription')
            ->with($deactivateSubscriptionsRequestDTO)
            ->once()
            ->andReturn($subscriptionsResponseDTO);

        $result = $this->subject->deactivateSubscription($deactivateSubscriptionsRequestDTO);

        self::assertSame($subscriptionsResponseDTO, $result);
    }
}
