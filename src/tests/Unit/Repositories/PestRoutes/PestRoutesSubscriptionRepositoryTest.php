<?php

namespace Tests\Unit\Repositories\PestRoutes;

use App\DTO\Subscriptions\ActivateSubscriptionRequestDTO;
use App\DTO\Subscriptions\CreateSubscriptionRequestDTO;
use App\DTO\Subscriptions\DeactivateSubscriptionRequestDTO;
use App\DTO\Subscriptions\SearchSubscriptionsDTO;
use App\Enums\SubscriptionStatus;
use App\Events\Subscription\SubscriptionCreated;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Interfaces\Repository\SubscriptionRepository;
use App\Models\External\SubscriptionModel;
use App\Repositories\Mappers\PestRoutesSubscriptionToExternalModelMapper;
use App\Repositories\PestRoutes\AbstractPestRoutesRepository;
use App\Repositories\PestRoutes\ParametersFactories\SubscriptionParametersFactory;
use App\Repositories\PestRoutes\PestRoutesSubscriptionRepository;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Exceptions\ResourceNotFoundException;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Params\CreateSubscriptionsParams;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Params\SearchSubscriptionsParams;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Params\UpdateSubscriptionsParams;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Subscription;
use Aptive\PestRoutesSDK\Resources\Subscriptions\SubscriptionsResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use ReflectionClass;
use Tests\Data\ServiceTypeData;
use Tests\Data\SubscriptionData;
use Tests\TestCase;
use Tests\Traits\PestRoutesClientMockBuilderAware;
use Tests\Traits\RandomIntTestData;
use Tests\Unit\Repositories\ExtendsAbstractExternalRepository;

class PestRoutesSubscriptionRepositoryTest extends TestCase
{
    use PestRoutesClientMockBuilderAware;
    use RandomIntTestData;
    use ExtendsAbstractPestRoutesRepository;
    use ExtendsAbstractExternalRepository;

    protected PestRoutesSubscriptionRepository $subject;

    public function setUp(): void
    {
        parent::setUp();

        $modelMapper = new PestRoutesSubscriptionToExternalModelMapper();
        $parametersFactory = new SubscriptionParametersFactory();

        $this->subject = new PestRoutesSubscriptionRepository($modelMapper, $parametersFactory);
    }

    protected function getSubject(): AbstractPestRoutesRepository
    {
        return $this->subject;
    }

    public function test_its_implements_subscription_repository_interface()
    {
        $class = new ReflectionClass(PestRoutesSubscriptionRepository::class);
        $this->assertTrue($class->implementsInterface(SubscriptionRepository::class));
    }

    public function test_it_loads_subscriptions()
    {
        $subscriptions = SubscriptionData::getTestTypedSubscriptions(ServiceTypeData::PRO, ServiceTypeData::MOSQUITO);

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(SubscriptionsResource::class)
            ->callSequense('subscriptions', 'includeData', 'search', 'all')
            ->methodExpectsArgs('search', function (SearchSubscriptionsParams $params) {
                $paramsArray = $params->toArray();

                return $paramsArray['officeIDs'] === [$this->getTestOfficeId()]
                    && $paramsArray['active'] === 1
                    && $paramsArray['customerIDs'] === [$this->getTestAccountNumber()];
            })
            ->willReturn(new PestRoutesCollection($subscriptions->toArray()))
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClientMock);

        $result = $this->subject
            ->office($this->getTestOfficeId())
            ->search(new SearchSubscriptionsDTO(
                officeIds: [$this->getTestOfficeId()],
                customerIds: [$this->getTestAccountNumber()],
                isActive: 1,
            ));

        $this->assertEquals(
            $subscriptions->map(fn (Subscription $subscription) => $subscription->id),
            $result->map(fn (SubscriptionModel $subscription) => $subscription->id)
        );
    }

    public function test_it_throws_entity_not_found_exception()
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->willThrow(new ResourceNotFoundException())
            ->mock();

        $this->expectException(EntityNotFoundException::class);

        $this->subject->setPestRoutesClient($pestRoutesClientMock);
        $this->subject
            ->office($this->getTestOfficeId())
            ->find($this->getTestAccountNumber());
    }

    public function test_it_searches_by_customer_id(): void
    {
        $subscriptions = SubscriptionData::getTestData();
        $customerId = $this->getTestAccountNumber();

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(SubscriptionsResource::class)
            ->methodExpectsArgs(
                'search',
                fn (SearchSubscriptionsParams $params) => $params->toArray() === [
                        'officeIDs' => [$this->getTestOfficeId()],
                        'active' => 1,
                        'customerIDs' => [$customerId],
                        'includeData' => 0,
                    ]
            )
            ->callSequense('subscriptions', 'includeData', 'search', 'all')
            ->willReturn(new PestRoutesCollection($subscriptions->all()))
            ->mock();

        $this->subject->setPestRoutesClient($clientMock);

        $result = $this->subject
            ->office($this->getTestOfficeId())
            ->searchBy('customerId', [$customerId]);

        $this->assertCount($subscriptions->count(), $result);
    }

    public function test_it_finds_single_subscription()
    {
        $subscription = SubscriptionData::getTestData()->first();

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(SubscriptionsResource::class)
            ->callSequense('subscriptions', 'find')
            ->methodExpectsArgs('find', [$this->getTestSubscriptionId()])
            ->willReturn($subscription)
            ->mock();

        $this->subject->setPestRoutesClient($pestRoutesClientMock);

        $result = $this->subject
            ->office($this->getTestOfficeId())
            ->find($this->getTestSubscriptionId());

        self::assertEquals($subscription->id, $result->id);
    }

    public function test_it_finds_many(): void
    {
        $ids = [
            $this->getTestSubscriptionId(),
            $this->getTestSubscriptionId() + 1,
        ];

        /** @var Collection<int, Subscription> $subscriptions */
        $subscriptions = SubscriptionData::getTestData(2);

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->resource(SubscriptionsResource::class)
            ->callSequense('subscriptions', 'includeData', 'search', 'all')
            ->methodExpectsArgs(
                'search',
                function (SearchSubscriptionsParams $params) use ($ids) {
                    $array = $params->toArray();

                    return $array['officeIDs'] === [$this->getTestOfficeId()]
                        && $array['subscriptionIDs'] === $ids;
                }
            )
            ->willReturn(new PestRoutesCollection($subscriptions->all()))
            ->mock();

        $this->subject->setPestRoutesClient($clientMock);

        $result = $this->subject
            ->office($this->getTestOfficeId())
            ->findMany(...$ids);

        $this->assertCount($subscriptions->count(), $result);
    }

    public function test_it_create_subscription(): void
    {
        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->callSequense('subscriptions', 'create')
            ->resource(SubscriptionsResource::class)
            ->methodExpectsArgs(
                'create',
                function (CreateSubscriptionsParams $params) {
                    $array = $params->toArray();

                    return $array['serviceID'] === $this->getTestServiceId() &&
                        $array['customerID'] === $this->getTestAccountNumber();
                }
            )
            ->willReturn($this->getTestSubscriptionId())
            ->mock();

        $this->subject->office($this->getTestOfficeId())->setPestRoutesClient($clientMock);

        Event::fake();

        $result = $this->subject->createSubscription($this->getCreateSubscriptionRequestDTO());

        Event::assertDispatched(SubscriptionCreated::class);

        $this->assertEquals(
            $this->getTestSubscriptionId(),
            $result->subscriptionId
        );
    }

    public function test_create_throws_internal_server_error_http_exception(): void
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->willThrow(new InternalServerErrorHttpException())
            ->mock();

        $this->subject->office($this->getTestOfficeId())->setPestRoutesClient($pestRoutesClientMock);

        $this->expectException(InternalServerErrorHttpException::class);

        Event::fake();

        $this->subject->createSubscription($this->getCreateSubscriptionRequestDTO());

        Event::assertNotDispatched(SubscriptionCreated::class);
    }

    public function test_it_activate_subscription(): void
    {
        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->callSequense('subscriptions', 'update')
            ->resource(SubscriptionsResource::class)
            ->methodExpectsArgs(
                'update',
                function (UpdateSubscriptionsParams $params) {
                    $array = $params->toArray();

                    return $array['subscriptionID'] === $this->getTestSubscriptionId() &&
                        $array['customerID'] === $this->getTestAccountNumber() &&
                        $array['active'] === (string) SubscriptionStatus::ACTIVE->value;
                }
            )
            ->willReturn($this->getTestSubscriptionId())
            ->mock();

        $this->subject->office($this->getTestOfficeId())->setPestRoutesClient($clientMock);

        $result = $this->subject->activateSubscription($this->getActivateSubscriptionRequestDTO());

        $this->assertEquals(
            $this->getTestSubscriptionId(),
            $result->subscriptionId
        );
    }

    public function test_it_deactivate_subscription(): void
    {
        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office($this->getTestOfficeId())
            ->callSequense('subscriptions', 'update')
            ->resource(SubscriptionsResource::class)
            ->methodExpectsArgs(
                'update',
                function (UpdateSubscriptionsParams $params) {
                    $array = $params->toArray();

                    return $array['subscriptionID'] === $this->getTestSubscriptionId() &&
                        $array['active'] === (string) SubscriptionStatus::FROZEN->value;
                }
            )
            ->willReturn($this->getTestSubscriptionId())
            ->mock();

        $this->subject->office($this->getTestOfficeId())->setPestRoutesClient($clientMock);

        $result = $this->subject->deactivateSubscription(new DeactivateSubscriptionRequestDTO(
            subscriptionId: $this->getTestSubscriptionId(),
            customerId: $this->getTestAccountNumber(),
            officeId: $this->getTestOfficeId(),
        ));

        $this->assertEquals(
            $this->getTestSubscriptionId(),
            $result->subscriptionId
        );
    }

    private function getCreateSubscriptionRequestDTO(): CreateSubscriptionRequestDTO
    {
        return new CreateSubscriptionRequestDTO(
            serviceId: $this->getTestServiceId(),
            customerId: $this->getTestAccountNumber()
        );
    }

    private function getActivateSubscriptionRequestDTO(): ActivateSubscriptionRequestDTO
    {
        return new ActivateSubscriptionRequestDTO(
            subscriptionId: $this->getTestSubscriptionId(),
            customerId: $this->getTestAccountNumber(),
            officeId: $this->getTestOfficeId(),
        );
    }
}
