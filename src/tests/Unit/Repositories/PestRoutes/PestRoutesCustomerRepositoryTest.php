<?php

namespace Tests\Unit\Repositories\PestRoutes;

use App\DTO\Customer\SearchCustomersDTO;
use App\DTO\Customer\UpdateCommunicationPreferencesDTO;
use App\Interfaces\Repository\ServiceTypeRepository;
use App\Interfaces\Repository\SubscriptionRepository;
use App\Models\External\CustomerModel;
use App\Models\External\SubscriptionModel;
use App\Repositories\Mappers\PestRoutesCustomerToExternalModelMapper;
use App\Repositories\PestRoutes\AbstractPestRoutesRepository;
use App\Repositories\PestRoutes\ParametersFactories\CustomerParametersFactory;
use App\Repositories\PestRoutes\PestRoutesCustomerRepository;
use App\Repositories\PestRoutes\PestRoutesServiceTypeRepository;
use App\Repositories\PestRoutes\PestRoutesSubscriptionRepository;
use App\Repositories\RepositoryContext;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Client;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Resources\Customers\Customer;
use Aptive\PestRoutesSDK\Resources\Customers\CustomersResource;
use Aptive\PestRoutesSDK\Resources\Customers\Params\SearchCustomersParams;
use Aptive\PestRoutesSDK\Resources\Customers\Params\UpdateCustomersParams;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\CustomerData;
use Tests\Data\ServiceTypeData;
use Tests\Data\SubscriptionData;
use Tests\TestCase;
use Tests\Traits\PestRoutesClientMockBuilderAware;
use Tests\Traits\RandomIntTestData;
use Tests\Unit\Repositories\ExtendsAbstractExternalRepository;

class PestRoutesCustomerRepositoryTest extends TestCase
{
    use RandomIntTestData;
    use PestRoutesClientMockBuilderAware;
    use ExtendsAbstractPestRoutesRepository;
    use ExtendsAbstractExternalRepository;

    private const EMAIL = 'test@email.com';
    private const GLOBAL_OFFICE_ID = 0;

    public PestRoutesCustomerRepository $pestRoutesCustomerRepository;

    public function setUp(): void
    {
        parent::setUp();

        $modelMapper = new PestRoutesCustomerToExternalModelMapper();
        $parametersFactory = new CustomerParametersFactory();

        $this->pestRoutesCustomerRepository = new PestRoutesCustomerRepository($modelMapper, $parametersFactory);
    }

    protected function getSubject(): AbstractPestRoutesRepository
    {
        return $this->pestRoutesCustomerRepository;
    }

    public function test_it_updates_communication_preferences(): void
    {
        $officeId = $this->getTestOfficeId();
        $customerId = $this->getTestAccountNumber();
        $enableSms = random_int(0, 1);
        $enableEmails = random_int(0, 1);
        $enablePhoneCalls = random_int(0, 1);

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($officeId)
            ->resource(CustomersResource::class)
            ->callSequense('customers', 'update')
            ->methodExpectsArgs(
                'update',
                fn (UpdateCustomersParams $params) => $params->toArray() === [
                        'customerID' => $customerId,
                        'smsReminders' => (string) $enableSms,
                        'phoneReminders' => (string) $enablePhoneCalls,
                        'emailReminders' => (string) $enableEmails,
                    ]
            )
            ->willReturn($customerId)
            ->mock();

        $this->pestRoutesCustomerRepository->setPestRoutesClient($pestRoutesClientMock);

        $updatedCustomerId = $this
            ->pestRoutesCustomerRepository
            ->updateCustomerCommunicationPreferences(
                new UpdateCommunicationPreferencesDTO(
                    officeId: $officeId,
                    accountNumber: $customerId,
                    smsReminders: $enableSms === 1,
                    emailReminders: $enableEmails === 1,
                    phoneReminders: $enablePhoneCalls === 1
                )
            );

        $this->assertSame($customerId, $updatedCustomerId);
    }

    public function test_update_communication_preferences_passes_exception(): void
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->willThrow(new InternalServerErrorHttpException())
            ->mock();

        $this->pestRoutesCustomerRepository->setPestRoutesClient($pestRoutesClientMock);

        $this->expectException(InternalServerErrorHttpException::class);

        $this->pestRoutesCustomerRepository->updateCustomerCommunicationPreferences(
            new UpdateCommunicationPreferencesDTO(
                officeId: $this->getTestOfficeId(),
                accountNumber: $this->getTestAccountNumber(),
                smsReminders: true,
                emailReminders: true,
                phoneReminders: true
            )
        );
    }

    public function test_it_searches_active_customers_by_email(): void
    {
        Config::set('pestroutes.auth.global_office_id', self::GLOBAL_OFFICE_ID);
        Config::set('pestroutes.max_office_id', $this->getTestOfficeId());
        $customersCollection = CustomerData::getTestEntityData(3);

        $officeIds = range(1, 100);
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(0)
            ->resource(CustomersResource::class)
            ->callSequense('customers', 'includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchCustomersParams $params) use ($officeIds) {
                    $paramsArray = $params->toArray();

                    return empty($paramsArray['customerIDs'])
                        && $paramsArray['email'] === self::EMAIL
                        && $paramsArray['officeIDs'] === $officeIds;
                }
            )
            ->willReturn(new PestRoutesCollection($customersCollection->all()))
            ->mock();

        $this->pestRoutesCustomerRepository->setPestRoutesClient($pestRoutesClientMock);

        $result = $this->pestRoutesCustomerRepository
            ->office(0)
            ->searchActiveCustomersByEmail(self::EMAIL, $officeIds);

        self::assertInstanceOf(Collection::class, $result);
        self::assertEquals(
            $customersCollection->map(fn (CustomerModel $customer) => $customer->id),
            $result->map(fn (CustomerModel $customer) => $customer->id)
        );
    }

    protected function givenRoutesClientMockReturnsCustomer($customer): Client|MockInterface
    {
        return $this->getPestRoutesClientMockBuilder()
            ->resource(CustomersResource::class)
            ->callSequense('customers', 'find')
            ->willReturn($customer)
            ->mock();
    }

    public function test_it_loads_related_subscriptions_with_nested_service_type(): void
    {
        /** @var CustomerModel $customerModel */
        $customerModel = CustomerData::getTestEntityData()->first();

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(CustomersResource::class)
            ->callSequense('customers', 'includeData', 'search', 'all')
            ->willReturn(new PestRoutesCollection(
                CustomerData::getTestData(1, ['customerID' => $customerModel->id])->toArray()
            ))
            ->mock();

        $this->pestRoutesCustomerRepository->setPestRoutesClient($pestRoutesClientMock);

        $subscriptionRepositoryMock = Mockery::mock(PestRoutesSubscriptionRepository::class)->makePartial();
        $subscriptionRepositoryMock->setContext(new RepositoryContext());
        $this->instance(SubscriptionRepository::class, $subscriptionRepositoryMock);

        $subscriptionsCollection = SubscriptionData::getTestEntityData(
            2,
            [
                'customerID' => $customerModel->id,
                'serviceID' => ServiceTypeData::PRO,
            ],
            [
                'customerID' => $customerModel->id,
                'serviceID' => ServiceTypeData::PRO,
            ]
        );

        $subscriptionRepositoryMock
            ->shouldReceive('searchBy')
            ->withArgs(['customerId', [$customerModel->id]])
            ->andReturn($subscriptionsCollection)
            ->once();

        $serviceTypeRepositoryMock = Mockery::mock(PestRoutesServiceTypeRepository::class)->makePartial();
        $serviceTypeRepositoryMock->setContext(new RepositoryContext());
        $this->instance(ServiceTypeRepository::class, $serviceTypeRepositoryMock);

        $serviceType = ServiceTypeData::getTestEntityDataOfTypes(ServiceTypeData::PRO);
        $serviceTypeRepositoryMock
            ->shouldReceive('find')
            ->withArgs([ServiceTypeData::PRO])
            ->andReturn($serviceType)
            ->atLeast();

        /** @var Collection<int, CustomerModel> $result */
        $result = $this->pestRoutesCustomerRepository
            ->office($this->getTestOfficeId())
            ->withRelated(['subscriptions.serviceType'])
            ->search(new SearchCustomersDTO([$this->getTestOfficeId()]));

        $relatedSubscriptions = $result->first()->subscriptions;

        self::assertEquals($subscriptionsCollection, $relatedSubscriptions);
    }

    public function test_it_loads_related_subscriptions_for_single_customer(): void
    {
        $relations = ['subscriptions'];

        /** @var CustomerModel $customer */
        $customer = CustomerData::getTestEntityData()->first();

        /** @var Collection<int, SubscriptionModel> $subscriptions */
        $subscriptions = SubscriptionData::getTestEntityData();

        $subscriptionRepositoryMock = Mockery::mock(PestRoutesSubscriptionRepository::class)->makePartial();
        $subscriptionRepositoryMock->setContext(new RepositoryContext());
        $this->instance(SubscriptionRepository::class, $subscriptionRepositoryMock);

        $subscriptionRepositoryMock
            ->shouldReceive('searchBy')
            ->withArgs(['customerId', [$customer->id]])
            ->andReturn($subscriptions)
            ->once();

        /** @var CustomerModel $result */
        $result = $this->pestRoutesCustomerRepository
            ->office($this->getTestOfficeId())
            ->withRelated($relations)
            ->loadAllRelations($customer);

        self::assertSame($customer, $result);
        self::assertSame($subscriptions, $result->subscriptions);
    }

    public function test_it_finds_many(): void
    {
        $ids = [
            $this->getTestAccountNumber(),
            $this->getTestAccountNumber() + 1,
        ];

        /** @var Collection<int, Customer> $customers */
        $customers = CustomerData::getTestData(2);

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(CustomersResource::class)
            ->callSequense('customers', 'includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchCustomersParams $params) use ($ids) {
                    $array = $params->toArray();

                    return $array['officeIDs'] === [$this->getTestOfficeId()]
                        && $array['customerIDs'] === $ids;
                }
            )
            ->willReturn(new PestRoutesCollection($customers->all()))
            ->mock();

        $this->pestRoutesCustomerRepository->setPestRoutesClient($clientMock);

        $result = $this->pestRoutesCustomerRepository
            ->office($this->getTestOfficeId())
            ->findMany(...$ids);

        $this->assertCount($customers->count(), $result);
    }
}
