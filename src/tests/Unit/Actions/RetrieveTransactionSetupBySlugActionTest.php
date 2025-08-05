<?php

namespace Tests\Unit\Actions;

use App\Actions\RetrieveTransactionSetupBySlugAction;
use App\Exceptions\Account\AccountFrozenException;
use App\Exceptions\TransactionSetup\TransactionSetupException;
use App\Exceptions\TransactionSetup\TransactionSetupExpiredException;
use App\Helpers\FormatHelper;
use App\Interfaces\Repository\CustomerRepository;
use App\Models\Account;
use App\Models\External\CustomerModel;
use App\Models\TransactionSetup;
use App\Services\AccountService;
use App\Services\TransactionSetupService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ItemNotFoundException;
use Mockery;
use Mockery\MockInterface;
use Tests\Data\CustomerData;
use Tests\Data\SubscriptionData;
use Tests\TestCase;
use Tests\Traits\RandomIntTestData;

class RetrieveTransactionSetupBySlugActionTest extends TestCase
{
    use RefreshDatabase;
    use RandomIntTestData;

    public $slug = '2Fb2wr';
    public $accountId = '2550260';
    public $mockTransactionSetupService;
    public $mockAccountService;
    public $transactionSetup;
    public CustomerModel $customer;
    public MockInterface|CustomerRepository $customerRepositoryMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->mockTransactionSetupService = Mockery::mock(TransactionSetupService::class);
        $this->mockAccountService = Mockery::mock(AccountService::class);
        $this->transactionSetup = TransactionSetup::factory()->make(['account_number' => $this->accountId]);
        $this->customer = CustomerData::getTestEntityData(1, ['customerID' => $this->accountId])->first();
        $this->customerRepositoryMock = Mockery::mock(CustomerRepository::class);
    }

    private function getAccount(): Account
    {
        return Account::factory()->make([
            'account_number' => $this->accountId,
            'office_id' => $this->getTestOfficeId(),
        ]);
    }

    public function test_retrieves_transaction_setup_by_slug()
    {
        $this->setupMockTransactionSetupService($this->transactionSetup);

        $account = $this->getAccount();

        $this->mockAccountService->shouldReceive('getAccountByAccountNumber')
            ->with($account->account_number)
            ->andReturn($account)
            ->once();

        $this->customerRepositoryMock
            ->shouldReceive('office')
            ->withArgs([$account->office_id])
            ->once()
            ->andReturn($this->customerRepositoryMock);

        $this->customerRepositoryMock
            ->shouldReceive('withRelated')
            ->withArgs([['subscriptions']])
            ->once()
            ->andReturn($this->customerRepositoryMock);

        $subscriptions = SubscriptionData::getTestEntityData(1, [
            'customerID' => $account->account_number,
            'officeID' => $account->office_id,
        ]);

        $this->customer->setRelated('subscriptions', $subscriptions);
        $this->customerRepositoryMock
            ->shouldReceive('find')
            ->withArgs([$account->account_number])
            ->once()
            ->andReturn($this->customer);

        $transactionSetupAction = $this->setupTransactionSetupAction();

        $result = ($transactionSetupAction)($this->slug);

        $expectedArray = $this->transactionSetup->toArray();
        $expectedArray['customer'] = [
            'officeID' => $this->customer->officeId,
            'email' => $this->customer->email,
            'dueDate' => $this->customer->getDueDate(),
            'id' => $this->customer->id,
            'name' => $this->customer->getFullName(),
            'first_name' => $this->customer->firstName,
            'last_name' => $this->customer->lastName,
            'phone_number' => $this->customer->getFirstPhone(),
            'status_name' => $this->customer->status->name,
            'office_id' => $this->customer->officeId,
            'is_phone_number_valid' => FormatHelper::isValidPhone($this->customer->getFirstPhone()),
            'is_email_valid' => FormatHelper::isValidEmail($this->customer->email),
            'auto_pay' => (bool) $this->customer->autoPay->numericValue(),
            'payment_profile_id' => $this->customer->autoPayPaymentProfileId,
            'balance_cents' => $this->customer->getBalanceCents(),
        ];

        self::assertEquals($expectedArray, $result);
    }

    public function test_throws_TransactionSetupExpiredException_for_expited_TransactionSetup()
    {
        $expiredTransactionSetup = TransactionSetup::factory()->make([
            'account_number' => $this->accountId,
            'created_at' => Carbon::createMidnightDate(2022, 1, 1),
            'updated_at' => Carbon::createMidnightDate(2022, 1, 1),
        ]);
        $this->setupMockTransactionSetupService($expiredTransactionSetup);
        $this->mockAccountService->shouldNotReceive('getValidCustomerByAccountNumber');

        $transactionSetupAction = $this->setupTransactionSetupAction();

        $this->expectException(TransactionSetupExpiredException::class);

        ($transactionSetupAction)($this->slug);
    }

    public function test_throws_transactionsetupexception_on_itemnotfoundexception()
    {
        $this->mockTransactionSetupService->shouldReceive('findBySlug')
            ->with($this->slug)
            ->andThrow(new ItemNotFoundException());

        $transactionSetupAction = $this->setupTransactionSetupAction();

        $this->expectException(TransactionSetupException::class);
        ($transactionSetupAction)($this->slug);
    }

    public function test_throws_modelnotfoundexception_on_modelnotfoundexception()
    {
        $this->setupMockTransactionSetupService($this->transactionSetup);
        $this->mockAccountService->shouldReceive('getAccountByAccountNumber')
            ->andThrow(ModelNotFoundException::class);

        $transactionSetupAction = $this->setupTransactionSetupAction();

        $this->expectException(ModelNotFoundException::class);
        ($transactionSetupAction)($this->slug);
    }

    public function test_throws_modelnotfoundexception_on_accountfrozenexception()
    {
        $this->setupMockTransactionSetupService($this->transactionSetup);
        $this->mockAccountService->shouldReceive('getValidCustomerByAccountNumber')
            ->andThrow(new AccountFrozenException());

        $transactionSetupAction = $this->setupTransactionSetupAction();

        $this->expectException(TransactionSetupException::class);
        ($transactionSetupAction)($this->slug);
    }

    protected function setupTransactionSetupAction()
    {
        return new RetrieveTransactionSetupBySlugAction(
            $this->mockAccountService,
            $this->mockTransactionSetupService,
            $this->customerRepositoryMock,
        );
    }

    protected function setupMockTransactionSetupService($transactionSetup)
    {
        $this->mockTransactionSetupService->shouldReceive('findBySlug')
            ->with($this->slug)
            ->andReturn($transactionSetup)
            ->once();
    }
}
