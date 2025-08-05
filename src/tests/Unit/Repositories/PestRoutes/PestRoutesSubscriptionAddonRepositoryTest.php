<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\PestRoutes;

use App\DTO\Subscriptions\SubscriptionAddonRequestDTO;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Repositories\Mappers\PestRoutesSubscriptionAddonToExternalModelMapper;
use App\Repositories\PestRoutes\AbstractPestRoutesRepository;
use App\Repositories\PestRoutes\ParametersFactories\OfficeParametersFactory;
use App\Repositories\PestRoutes\PestRoutesSubscriptionAddonRepository;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Client;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Subscriptions\SubscriptionAddon;
use Aptive\PestRoutesSDK\Resources\Subscriptions\SubscriptionAddonsResource;
use Aptive\PestRoutesSDK\Resources\Subscriptions\SubscriptionsResource;
use Tests\Data\SubscriptionAddonData;
use Tests\Traits\PestRoutesClientMockBuilderAware;
use Tests\Traits\RandomIntTestData;
use Tests\Unit\Repositories\ExtendsAbstractExternalRepository;

final class PestRoutesSubscriptionAddonRepositoryTest extends GenericRepositoryWithoutSearchTest
{
    use RandomIntTestData;
    use PestRoutesClientMockBuilderAware;
    use ExtendsAbstractExternalRepository;

    protected PestRoutesSubscriptionAddonRepository $subscriptionAddonsRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->subscriptionAddonsRepository = new PestRoutesSubscriptionAddonRepository(
            new PestRoutesSubscriptionAddonToExternalModelMapper(),
            new OfficeParametersFactory(),
        );
    }

    protected function getSubject(): AbstractPestRoutesRepository
    {
        return $this->subscriptionAddonsRepository;
    }

    public function test_it_create_subscription_addons(): void
    {
        /** @var SubscriptionAddon $subscriptionAddon */
        $subscriptionAddon = SubscriptionAddonData::getTestEntityData(1, [
            'addOnID' => $this->getTestSubscriptionAddonId(),
        ])->first();

        $subscriptionAddonResource = \Mockery::mock(SubscriptionAddonsResource::class);
        $subscriptionAddonResource
            ->shouldReceive('create')
            ->andReturn($subscriptionAddon->id);

        $subscriptionResource = \Mockery::mock(SubscriptionsResource::class);
        $subscriptionResource
            ->shouldReceive('initialAddons')
            ->andReturn($subscriptionAddonResource);

        $officeResource = \Mockery::mock(OfficesResource::class);
        $officeResource
            ->shouldReceive('subscriptions')
            ->andReturn($subscriptionResource);

        $pestRoutesClientMock = \Mockery::mock(Client::class);
        $pestRoutesClientMock
            ->shouldReceive('office')
            ->withArgs([$this->getTestOfficeId()])
            ->andReturn($officeResource);

        $this->getSubject()->setPestRoutesClient($pestRoutesClientMock);

        self::assertEquals(
            $this->getTestSubscriptionAddonId(),
            $this
                ->getSubject()
                ->office($this->getTestOfficeId())
                ->createInitialAddon($this->getTestSubscriptionId(), $this->getTestSubscriptionAddonRequestDTO())
        );
    }

    public function test_create_throws_internal_server_error_http_exception(): void
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->willThrow(new InternalServerErrorHttpException())
            ->mock();

        $this->getSubject()->office($this->getTestOfficeId())->setPestRoutesClient($pestRoutesClientMock);

        $this->expectException(InternalServerErrorHttpException::class);

        $this
            ->getSubject()
            ->createInitialAddon($this->getTestSubscriptionId(), $this->getTestSubscriptionAddonRequestDTO());
    }

    public function test_create_throws_office_not_set_exception(): void
    {
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->willThrow(new OfficeNotSetException())
            ->mock();

        $this->getSubject()->setPestRoutesClient($pestRoutesClientMock);

        $this->expectException(OfficeNotSetException::class);

        $this
            ->getSubject()
            ->createInitialAddon($this->getTestSubscriptionId(), $this->getTestSubscriptionAddonRequestDTO());
    }

    private function getTestSubscriptionAddonRequestDTO(): SubscriptionAddonRequestDTO
    {
        return new SubscriptionAddonRequestDTO(
            productId: $this->getTestProductId(),
            amount: 199,
            description: 'Test Description',
            quantity: 1,
            taxable: true,
            serviceId: $this->getTestServiceId(),
            creditTo: 0,
            officeId: $this->getTestOfficeId()
        );
    }
}
