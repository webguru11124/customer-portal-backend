<?php

declare(strict_types=1);

namespace Tests\Unit\Http\API\V2\Controller;

use App\Actions\Subscription\ActivateSubscriptionAction;
use App\Actions\Subscription\CreateFrozenSubscriptionAction;
use App\Actions\Subscription\ShowSubscriptionsAction;
use App\DTO\Subscriptions\ActivateSubscriptionResponseDTO;
use App\DTO\Subscriptions\CreateSubscriptionResponseDTO;
use App\Http\Requests\CreateSubscriptionRequest;
use App\Models\Account;
use App\Models\External\SubscriptionModel;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Data\CreateSubscriptionRequestData;
use Tests\Data\ServiceTypeData;
use Tests\Data\SubscriptionData;
use Tests\Traits\ExpectedV2ResponseData;
use Tests\Traits\RandomIntTestData;
use Tests\Traits\TestAuthorizationMiddleware;

class SubscriptionControllerTest extends ApiTestCase
{
    use ExpectedV2ResponseData;
    use RandomIntTestData;
    use RefreshDatabase;
    use TestAuthorizationMiddleware;

    public MockInterface|ShowSubscriptionsAction $showSubscriptionsAction;
    public MockInterface|CreateFrozenSubscriptionAction $createFrozenSubscriptionsAction;
    public MockInterface|ActivateSubscriptionAction $activateSubscriptionAction;
    public Account $account;

    public function setUp(): void
    {
        parent::setUp();

        $this->showSubscriptionsAction = Mockery::mock(ShowSubscriptionsAction::class);
        $this->createFrozenSubscriptionsAction = Mockery::mock(CreateFrozenSubscriptionAction::class);
        $this->activateSubscriptionAction = Mockery::mock(ActivateSubscriptionAction::class);
        $this->account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);
    }

    public function test_get_user_subscriptions_returns_subscriptions(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $subscriptions = SubscriptionData::getTestEntityData(
            2,
            ['nextService' => '2022-08-13'],
            ['nextService' => '2022-09-30'],
        );
        $serviceType = ServiceTypeData::getTestEntityData()->first();
        $subscriptions->each(
            fn (SubscriptionModel $subscriptions) => $subscriptions->setRelated('serviceType', $serviceType)
        );

        $this->showSubscriptionsAction
            ->shouldReceive('__invoke')
            ->withArgs(
                fn (Account $account) => $account->office_id === $this->getTestOfficeId()
                    && $account->account_number === $this->getTestAccountNumber()
            )->once()
            ->andReturn($subscriptions);

        $this->instance(ShowSubscriptionsAction::class, $this->showSubscriptionsAction);

        $this->getUserSubscriptionsJsonResponse()
            ->assertOk()
            ->assertJsonPath('data.0.type', 'Subscription')
            ->assertJsonPath('data.0.id', (string) $subscriptions[0]->id)
            ->assertJsonPath('data.0.attributes.serviceType', $serviceType->description)
            ->assertJsonPath('links.self', '/api/v2/customer/' . $this->getTestAccountNumber() . '/subscriptions');
    }

    public function test_get_user_subscriptions_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->getUserSubscriptionsJsonResponse()
        );
    }

    public function test_get_user_subscriptions_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();

        $this->getUserSubscriptionsJsonResponse()
            ->assertNotFound();
    }

    public function test_get_user_subscriptions_returns_valid_response_on_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $this->showSubscriptionsAction->shouldReceive('__invoke')->andThrow(new Exception());

        $this->instance(ShowSubscriptionsAction::class, $this->showSubscriptionsAction);

        $response = $this->getUserSubscriptionsJsonResponse();
        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    public function test_create_frozen_subscription_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->postCreateFrozenSubscriptionJsonResponse()
        );
    }

    public function test_create_frozen_subscription_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();

        $this->postCreateFrozenSubscriptionJsonResponse()->assertNotFound();
    }

    public function test_create_frozen_subscription_returns_valid_response_on_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $this->showSubscriptionsAction->shouldReceive('__invoke')->andThrow(new Exception());

        $this->instance(CreateFrozenSubscriptionAction::class, $this->createFrozenSubscriptionsAction);

        $response = $this->postCreateFrozenSubscriptionJsonResponse();
        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function test_create_frozen_subscription_returns_subscription(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $subscription = new CreateSubscriptionResponseDTO(
            subscriptionId: $this->getTestSubscriptionId()
        );

        $this->createFrozenSubscriptionsAction
            ->shouldReceive('__invoke')
            ->withArgs([Account::class, CreateSubscriptionRequest::class])
            ->once()
            ->andReturn($subscription);

        $this->instance(CreateFrozenSubscriptionAction::class, $this->createFrozenSubscriptionsAction);

        $this
            ->postCreateFrozenSubscriptionJsonResponse()
            ->assertOk()
            ->assertJsonPath('subscriptionId', $subscription->subscriptionId);
    }

    public function test_activate_subscription_requires_authentication(): void
    {
        $this->checkAuthorizationMiddleware(
            fn () => $this->patchActivateSubscriptionJsonResponse()
        );
    }

    public function test_activate_subscription_requires_authorization(): void
    {
        $this->createAndLogInAuth0User();

        $this->patchActivateSubscriptionJsonResponse()->assertNotFound();
    }

    public function test_activate_subscription_returns_valid_response_on_exception(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $this->showSubscriptionsAction->shouldReceive('__invoke')->andThrow(new Exception());

        $this->instance(ActivateSubscriptionAction::class, $this->activateSubscriptionAction);

        $response = $this->patchActivateSubscriptionJsonResponse();
        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function test_activate_subscription_returns_subscription(): void
    {
        $this->createAndLogInAuth0UserWithAccount();
        $subscription = new ActivateSubscriptionResponseDTO(
            subscriptionId: $this->getTestSubscriptionId()
        );

        $this->activateSubscriptionAction
            ->shouldReceive('__invoke')
            ->withArgs([Account::class, $this->getTestSubscriptionId()])
            ->once()
            ->andReturn($subscription);

        $this->instance(ActivateSubscriptionAction::class, $this->activateSubscriptionAction);

        $this
            ->patchActivateSubscriptionJsonResponse()
            ->assertOk()
            ->assertJsonPath('subscriptionId', $subscription->subscriptionId);
    }

    protected function getUserSubscriptionsJsonResponse(): TestResponse
    {
        return $this->getJson(
            route('api.v2.customer.subscriptions.get', ['accountNumber' => $this->getTestAccountNumber()])
        );
    }

    protected function postCreateFrozenSubscriptionJsonResponse(): TestResponse
    {
        return $this->postJson(
            route('api.v2.customer.subscription.createFrozen', ['accountNumber' => $this->getTestAccountNumber()]),
            CreateSubscriptionRequestData::getRequest()
        );
    }

    protected function patchActivateSubscriptionJsonResponse(): TestResponse
    {
        return $this->patchJson(
            route(
                'api.v2.customer.subscription.activateSubscription',
                [
                    'accountNumber' => $this->getTestAccountNumber(),
                    'subscriptionId' => $this->getTestSubscriptionId(),
                ]
            ),
        );
    }
}
