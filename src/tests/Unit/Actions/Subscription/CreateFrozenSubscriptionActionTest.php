<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Subscription;

use App\Actions\Subscription\CreateFrozenSubscriptionAction;
use App\DTO\Subscriptions\CreateSubscriptionRequestDTO;
use App\DTO\Subscriptions\CreateSubscriptionResponseDTO;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Http\Requests\CreateSubscriptionRequest;
use App\Interfaces\Repository\SubscriptionRepository;
use App\Models\Account;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Mockery\MockInterface;
use Tests\Data\CreateSubscriptionRequestData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

final class CreateFrozenSubscriptionActionTest extends TestCase
{
    use RandomIntTestData;

    protected CreateFrozenSubscriptionAction $subject;
    protected MockInterface|SubscriptionRepository $subscriptionRepository;
    protected MockInterface|CreateSubscriptionRequest $request;
    protected Account $account;

    public function setUp(): void
    {
        parent::setUp();

        $this->subscriptionRepository = \Mockery::mock(SubscriptionRepository::class);
        $this->subject = new CreateFrozenSubscriptionAction($this->subscriptionRepository);

        $this->account = Account::factory()->makeOne([
            'account_number' => $this->getTestAccountNumber(),
            'office_id' => $this->getTestOfficeId(),
        ]);

        $this->mockRequest();
    }

    public function test_it_creates_subscription(): void
    {
        $subscriptionResponseDTO = new CreateSubscriptionResponseDTO(
            subscriptionId: $this->getTestSubscriptionId(),
        );

        $this->subscriptionRepository
            ->shouldReceive('office')
            ->withArgs([$this->getTestOfficeId()]);

        $this->subscriptionRepository
            ->shouldReceive('createSubscription')
            ->withArgs([CreateSubscriptionRequestDTO::class])
            ->andReturn($subscriptionResponseDTO)
            ->once();

        self::assertEquals($subscriptionResponseDTO, ($this->subject)($this->account, $this->request));
    }

    /**
     * @dataProvider provideExceptionsData
     */
    public function test_it_throws_exceptions(
        string $exception
    ): void {
        $this->subscriptionRepository
            ->shouldReceive('office')
            ->andReturn($this->subscriptionRepository);

        $this->subscriptionRepository
            ->shouldReceive('createSubscription')
            ->andThrow(new $exception());

        $this->expectException($exception);

        ($this->subject)($this->account, $this->request);
    }

    /**
     * @return iterable<string, array<int, string>>
     */
    public function provideExceptionsData(): iterable
    {
        yield 'internal_server_error' => [InternalServerErrorHttpException::class];
        yield 'office_not_set' => [OfficeNotSetException::class];
    }

    private function mockRequest(): void
    {
        $this->request = \Mockery::mock(CreateSubscriptionRequest::class);
        $this->request
            ->shouldReceive('all')
            ->andReturn(CreateSubscriptionRequestData::getRequest());
    }
}
