<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Subscription;

use App\Actions\Subscription\ActivateSubscriptionAction;
use App\DTO\Subscriptions\ActivateSubscriptionResponseDTO;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Http\Requests\CreateSubscriptionRequest;
use App\Interfaces\Repository\SubscriptionRepository;
use App\Models\Account;
use App\Models\External\SubscriptionModel;
use App\Services\SubscriptionService;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Mockery\MockInterface;
use Tests\Data\SubscriptionData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

final class ActivateSubscriptionActionTest extends TestCase
{
    use RandomIntTestData;

    protected ActivateSubscriptionAction $subject;
    protected MockInterface|SubscriptionService $subscriptionService;
    protected MockInterface|SubscriptionRepository $subscriptionRepository;
    protected MockInterface|CreateSubscriptionRequest $request;
    protected Account $account;

    public function setUp(): void
    {
        parent::setUp();

        $this->subscriptionService = \Mockery::mock(SubscriptionService::class);
        $this->subscriptionRepository = \Mockery::mock(SubscriptionRepository::class);
        $this->subject = new ActivateSubscriptionAction(
            $this->subscriptionService,
            $this->subscriptionRepository
        );

        $this->account = Account::factory()->makeOne([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]);
    }

    public function test_it_activate_subscription(): void
    {
        $activateSubscriptionResponseDTO = new ActivateSubscriptionResponseDTO(
            subscriptionId: $this->getTestSubscriptionId(),
        );

        $subscriptions = SubscriptionData::getTestEntityData(1);

        $this->setupSubscriptionRepositoryToReturnValidSubscription($subscriptions->first());

        $this->subscriptionService
            ->shouldReceive('activateSubscription')
            ->once()
            ->withArgs([$this->account, $subscriptions->first()])
            ->andReturn($activateSubscriptionResponseDTO);

        self::assertEquals($activateSubscriptionResponseDTO, ($this->subject)($this->account, $this->getTestSubscriptionId()));
    }

    /**
     * @dataProvider provideExceptionsData
     */
    public function test_it_throws_exceptions(
        string $exception
    ): void {
        $subscriptions = SubscriptionData::getTestEntityData(1);

        $this->setupSubscriptionRepositoryToReturnValidSubscription($subscriptions->first());

        $this->subscriptionService
            ->shouldReceive('activateSubscription')
            ->andThrow(new $exception());

        $this->expectException($exception);

        ($this->subject)($this->account, $this->getTestSubscriptionId());
    }

    /**
     * @return iterable<string, array<int, string>>
     */
    public function provideExceptionsData(): iterable
    {
        yield 'internal_server_error' => [InternalServerErrorHttpException::class];
        yield 'office_not_set' => [OfficeNotSetException::class];
        yield 'entity_not_found_exception' => [EntityNotFoundException::class];
    }

    protected function setupSubscriptionRepositoryToReturnValidSubscription(SubscriptionModel $subscription): void
    {
        $this->subscriptionRepository
            ->shouldReceive('office')
            ->once()
            ->withArgs([$this->account->office_id])
            ->andReturn($this->subscriptionRepository);

        $this->subscriptionRepository
            ->shouldReceive('find')
            ->once()
            ->withArgs([$this->getTestSubscriptionId()])
            ->andReturn($subscription);
    }
}
