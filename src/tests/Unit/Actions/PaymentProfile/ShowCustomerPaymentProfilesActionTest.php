<?php

namespace Tests\Unit\Actions\PaymentProfile;

use App\Actions\PaymentProfile\ShowCustomerPaymentProfilesAction;
use App\DTO\PaymentProfile\SearchPaymentProfilesDTO;
use App\Enums\Models\PaymentProfile\PaymentMethod;
use App\Enums\Models\PaymentProfile\StatusType;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Exceptions\PestRoutesRepository\OfficeNotSetException;
use App\Interfaces\Repository\PaymentProfileRepository;
use App\Models\Account;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\PaymentProfileData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;
use Throwable;

class ShowCustomerPaymentProfilesActionTest extends TestCase
{
    use RandomIntTestData;

    protected ShowCustomerPaymentProfilesAction $subject;
    protected MockInterface|PaymentProfileRepository $paymentProfileRepositoryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->paymentProfileRepositoryMock = Mockery::mock(PaymentProfileRepository::class);
        $this->subject = new ShowCustomerPaymentProfilesAction($this->paymentProfileRepositoryMock);
    }

    public function test_it_searches_payment_profile(): void
    {
        /** @var Account $account */
        $account = Account::factory()->make([
            'office_id' => $this->getTestOfficeId(),
            'account_number' => $this->getTestAccountNumber(),
        ]);

        $statuses = [StatusType::VALID, StatusType::FAILED];
        $paymentMethods = [PaymentMethod::CREDIT_CARD];

        $this->paymentProfileRepositoryMock
            ->shouldReceive('office')
            ->with($account->office_id)
            ->once()
            ->andReturnSelf();

        $paymentProfiles = PaymentProfileData::getTestEntityData();

        $this->paymentProfileRepositoryMock
            ->shouldReceive('search')
            ->withArgs(fn (SearchPaymentProfilesDTO $dto) => $dto->officeId === $account->office_id
                && $dto->accountNumbers === [$account->account_number]
                && $dto->statuses === $statuses
                && $dto->paymentMethods === $paymentMethods)
            ->once()
            ->andReturn($paymentProfiles);
        $result = ($this->subject)($account, $statuses, $paymentMethods);

        self::assertSame($paymentProfiles, $result);
    }

    /**
     * @dataProvider repositoryExceptionsDataProvider
     */
    public function test_it_passes_repository_exceptions(Throwable $exception): void
    {
        /** @var Account $account */
        $account = Account::factory()->make();

        $this->paymentProfileRepositoryMock->shouldReceive('office')->andReturnSelf();
        $this->paymentProfileRepositoryMock->shouldReceive('search')->andThrow($exception);

        $this->expectException($exception::class);

        ($this->subject)($account, [], []);
    }

    public function repositoryExceptionsDataProvider(): iterable
    {
        yield [new OfficeNotSetException()];
        yield [new EntityNotFoundException()];
    }
}
