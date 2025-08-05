<?php

namespace Tests\Unit\Actions\Subscription;

use App\Actions\Subscription\ShowSubscriptionsAction;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\Entity\InvalidSearchedResourceException;
use App\Interfaces\Repository\SubscriptionRepository;
use App\Models\Account;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\SubscriptionData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use Throwable;

class ShowSubscriptionsActionTest extends TestCase
{
    use RandomIntTestData;

    protected MockInterface|SubscriptionRepository $subscriptionRepositoryMock;

    protected Account $account;
    protected ShowSubscriptionsAction $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subscriptionRepositoryMock = Mockery::mock(SubscriptionRepository::class);
        $this->subject = new ShowSubscriptionsAction($this->subscriptionRepositoryMock);

        $this->account = Account::factory()->makeOne([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]);
    }

    public function test_invoke_returns_customer_subscriptions(): void
    {
        $subscriptions = SubscriptionData::getTestData();

        $this->subscriptionRepositoryMock
            ->shouldReceive('office')
            ->with($this->account->office_id)
            ->once()
            ->andReturnSelf();

        $this->subscriptionRepositoryMock
            ->shouldReceive('withRelated')
            ->with(['serviceType'])
            ->once()
            ->andReturnSelf();

        $this->subscriptionRepositoryMock
            ->shouldReceive('searchByCustomerId')
            ->with([$this->account->account_number])
            ->once()
            ->andReturn($subscriptions);

        $result = ($this->subject)($this->account);

        self::assertSame($subscriptions, $result);
    }

    /**
     * @dataProvider repositoryExceptionsProvider
     */
    public function test_it_passes_repository_exceptions(Throwable $exception): void
    {
        $this->subscriptionRepositoryMock->shouldReceive('office')->andReturnSelf();

        $this->subscriptionRepositoryMock
            ->shouldReceive('withRelated')
            ->with(['serviceType'])
            ->once()
            ->andReturnSelf();

        $this->subscriptionRepositoryMock
            ->shouldReceive('searchByCustomerId')
            ->andThrow($exception);

        $this->expectException($exception::class);

        ($this->subject)($this->account);
    }

    /**
     * @return iterable<int, array<int, Throwable>>
     */
    public function repositoryExceptionsProvider(): iterable
    {
        yield [new EntityNotFoundException()];
        yield [new InvalidSearchedResourceException()];
    }
}
