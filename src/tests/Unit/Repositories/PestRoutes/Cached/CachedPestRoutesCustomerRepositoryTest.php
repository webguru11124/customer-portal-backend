<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes\Cached;

use App\Cache\AbstractCachedWrapper;
use App\DTO\Customer\UpdateCommunicationPreferencesDTO;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\External\CustomerModel;
use App\Models\External\SubscriptionModel;
use App\Repositories\AbstractExternalRepository;
use App\Repositories\PestRoutes\Cached\CachedPestRoutesCustomerRepository;
use App\Repositories\PestRoutes\PestRoutesCustomerRepository;
use App\Repositories\RepositoryContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\CustomerData;
use Tests\Data\SubscriptionData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class CachedPestRoutesCustomerRepositoryTest extends TestCase
{
    use RandomIntTestData;

    private const TTL_DEFAULT = 300;

    public const CACHE_STORE = 'array';

    protected CachedPestRoutesCustomerRepository $subject;
    protected MockInterface|PestRoutesCustomerRepository $pestRoutesCustomerRepositoryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->context = new RepositoryContext();
        $this->pestRoutesCustomerRepositoryMock = Mockery::mock(PestRoutesCustomerRepository::class);

        $this->subject = Mockery::mock(CachedPestRoutesCustomerRepository::class, [
            $this->pestRoutesCustomerRepositoryMock,
        ])->shouldAllowMockingProtectedMethods()->makePartial();
    }

    protected function getSubject(): AbstractCachedWrapper|CustomerRepository
    {
        return $this->subject;
    }

    protected function getWrappedRepositoryMock(): MockInterface|AbstractExternalRepository
    {
        return $this->pestRoutesCustomerRepositoryMock;
    }

    public function tearDown(): void
    {
        Cache::store(self::CACHE_STORE)->clear();

        parent::tearDown();
    }

    public function test_it_extends_abstract_cached_wrapper_class()
    {
        self::assertInstanceOf(AbstractCachedWrapper::class, $this->subject);
    }

    public function test_it_stores_search_active_customer_by_email_result_in_cache()
    {
        /** @var CustomerModel $serviceType */
        $customerCollection = CustomerData::getTestEntityData();
        $email = 'test@email.com';

        $officeIds = range(1, 100);

        $this->pestRoutesCustomerRepositoryMock
            ->shouldReceive('searchActiveCustomersByEmail')
            ->withArgs([$email, $officeIds, true])
            ->andReturn($customerCollection)
            ->once();

        for ($i = 0; $i < random_int(2, 10); $i++) {
            $result = $this->subject->searchActiveCustomersByEmail($email, $officeIds, true);

            self::assertSame($customerCollection, $result);
        }
    }

    /**
     * @dataProvider ttlDataProvider
     */
    public function test_it_provides_proper_ttl(string $methodName, int $ttl)
    {
        $instance = new class ($this->pestRoutesCustomerRepositoryMock) extends CachedPestRoutesCustomerRepository {
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
        yield ['searchActiveCustomersByEmail', self::TTL_DEFAULT];
    }

    public function test_update_customer_communication_preferences_doesnt_cache_result(): void
    {
        $dto = new UpdateCommunicationPreferencesDTO(
            officeId: $this->getTestOfficeId(),
            accountNumber: $this->getTestAccountNumber(),
            smsReminders: false,
            emailReminders: false,
            phoneReminders: false
        );

        $times = random_int(2, 5);

        $this->pestRoutesCustomerRepositoryMock
            ->shouldReceive('updateCustomerCommunicationPreferences')
            ->withArgs([$dto])
            ->andReturn($this->getTestAccountNumber())
            ->times($times);

        for ($i = 1; $i <= $times; $i++) {
            $result = $this->getSubject()->updateCustomerCommunicationPreferences($dto);

            self::assertSame($this->getTestAccountNumber(), $result);
        }
    }

    public function test_it_loads_relations_after_find(): void
    {
        $officeId = $this->getTestOfficeId();
        $accountNumber = $this->getTestAccountNumber();
        $relations = ['subscriptions'];

        /** @var CustomerModel $customer */
        $customer = CustomerData::getTestEntityData()->first();

        /** @var Collection<int, SubscriptionModel> $subscriptions */
        $subscriptions = SubscriptionData::getTestEntityData();

        $this->pestRoutesCustomerRepositoryMock
            ->shouldReceive('withRelated')
            ->with($relations)
            ->once()
            ->andReturnSelf();

        $this->pestRoutesCustomerRepositoryMock
            ->shouldReceive('withRelated')
            ->with([])
            ->once()
            ->andReturnSelf();

        $this->pestRoutesCustomerRepositoryMock
            ->shouldReceive('getContext')
            ->once()
            ->andReturn(new RepositoryContext());

        $this->pestRoutesCustomerRepositoryMock
            ->shouldReceive('office')
            ->with($officeId)
            ->once()
            ->andReturnSelf();

        $this->pestRoutesCustomerRepositoryMock
            ->shouldReceive('find')
            ->with($accountNumber)
            ->once()
            ->andReturn($customer);

        $this->pestRoutesCustomerRepositoryMock
            ->shouldReceive('loadAllRelations')
            ->with($customer)
            ->once()
            ->andReturn($customer->setRelated($relations[0], $subscriptions));

        $result = $this->getSubject()
            ->office($officeId)
            ->withRelated($relations)
            ->find($accountNumber);

        self::assertSame($customer, $result);
    }
}
